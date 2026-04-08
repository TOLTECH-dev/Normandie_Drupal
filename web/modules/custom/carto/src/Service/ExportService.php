<?php

declare(strict_types=1);

namespace Drupal\carto\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\normandie_core\Validator\NormandieValidator;

final class ExportService {

  public function __construct(
    protected readonly Connection $database,
    protected readonly LoggerChannelInterface $logger,
    protected readonly CartoService $cartoService,
    protected readonly NormandieValidator $validator,
    protected readonly ExtensionPathResolver $extensionPathResolver,
  ) {}

  public function exportPdf(int $type, array $params): array {
    if (!$this->validateExportParameters($type, $params)) {
      $this->logger->warning('Invalid export parameters: type=@type', ['@type' => $type]);
      return [
        'pdf' => NULL,
        'filename' => 'error_invalid_parameters',
        'error' => 'Invalid export parameters provided',
      ];
    }

    $data = $this->getFilteredPartners($type, $params);

    if (empty($data)) {
      return [
        'pdf' => NULL,
        'filename' => 'no_results_' . date('d_m_Y'),
        'error' => 'No partners found for the selected criteria',
      ];
    }

    // Organize data by department.
    $byDept = [];
    foreach ($data as $row) {
      $byDept[$row['DEPARTEMENT']][] = $row;
    }
    ksort($byDept);

    // Generate PDF.
    try {
      $pdfContent = $this->generatePdf($byDept, $type);
      $pdfBase64 = base64_encode($pdfContent);
      $filename = $this->getFilename($type);

      return [
        'pdf' => $pdfBase64,
        'filename' => $filename . '.pdf',
      ];
    }
    catch (\Exception $e) {
      $this->logger->error('PDF generation failed: @error', ['@error' => $e->getMessage()]);
      return [
        'pdf' => NULL,
        'filename' => 'error_pdf_generation_' . date('d_m_Y'),
        'error' => 'PDF generation failed: ' . $e->getMessage(),
      ];
    }
  }

  protected function getFilteredPartners(int $type, array $params): array {
    if (!empty($params['ville'])) {
      return $this->getPartenaireByVille($type, $params['ville']);
    }
    if (!empty($params['counties_codes'])) {
      return $this->getPartenaireByDepartement($type, $params['counties_codes']);
    }
    if (!empty($params['postal_codes'])) {
      return $this->getPartenaireByCodepostal($type, $params['postal_codes']);
    }
    if (!empty($params['epci'])) {
      return $this->getPartenaireByEpci($type, $params['epci']);
    }
    return $this->getPartenaireByType($type);
  }

  protected function getPartenaireByDepartement(int $type, string $codeDepartement): array {
    try {
      $query = $this->database->select('partenaire_', 'p');

      $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
      $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');
      $query->innerJoin('admin_coordonnee', 'ac', 'pag.id = ac.object_id');

      $query->fields('pag', ['adresse', 'nom', 'telephone', 'code_postal', 'ville', 'email', 'contact']);
      $query->addField('pad', 'tel_mobile');
      $query->addField('pad', 'complement');
      $query->addField('pr', 'complement', 'complement_identification');
      $query->addExpression("SUBSTRING(pag.code_postal, 1, 2)", 'departement');
      $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');

      $query->condition('ps.enabled', 1)
        ->condition('ac.type', '0 | %', 'LIKE')
        ->condition('p.type', $type . '%', 'LIKE')
        ->where('SUBSTRING(pag.code_postal, 1, 2) = :dept', [':dept' => $codeDepartement])
        ->orderBy('pag.nom', 'ASC');

      return $this->formatResults($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getPartenaireByDepartement: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  protected function getPartenaireByCodepostal(int $type, string $codepostal): array {
    try {
      $query = $this->database->select('partenaire_', 'p');

      $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
      $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');
      $query->innerJoin('admin_coordonnee', 'ac', 'pag.id = ac.object_id');

      $query->fields('pag', ['adresse', 'nom', 'telephone', 'code_postal', 'ville', 'email', 'contact']);
      $query->addField('pad', 'tel_mobile');
      $query->addField('pad', 'complement');
      $query->addField('pr', 'complement', 'complement_identification');
      $query->addExpression("SUBSTRING(pag.code_postal, 1, 2)", 'departement');
      $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');

      $query->condition('ps.enabled', 1)
        ->condition('ac.type', '0 | %', 'LIKE')
        ->condition('p.type', $type . '%', 'LIKE')
        ->condition('pag.code_postal', $codepostal)
        ->orderBy('pag.nom', 'ASC');

      return $this->formatResults($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getPartenaireByCodepostal: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  protected function getPartenaireByVille(int $type, string $codeinsee): array {
    try {
      $query = $this->database->select('partenaire_', 'p');

      $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
      $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');
      $query->innerJoin('admin_coordonnee', 'ac', 'pag.id = ac.object_id');

      $query->fields('pag', ['adresse', 'nom', 'telephone', 'code_postal', 'ville', 'email', 'contact']);
      $query->addField('pad', 'tel_mobile');
      $query->addField('pad', 'complement');
      $query->addField('pr', 'complement', 'complement_identification');
      $query->addExpression("SUBSTRING(pag.code_postal, 1, 2)", 'departement');
      $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');

      $query->condition('ps.enabled', 1)
        ->condition('ac.type', '0 | %', 'LIKE')
        ->condition('p.type', $type . '%', 'LIKE')
        ->condition('upv.code_insee', $codeinsee)
        ->orderBy('pag.nom', 'ASC');

      return $this->formatResults($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getPartenaireByVille: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  protected function getPartenaireByEpci(int $type, string $epciid): array {
    try {
      $query = $this->database->select('partenaire_', 'p');

      $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
      $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
      $query->innerJoin('up_ville', 'upv', 'upv.code_postal = pag.code_postal AND upv.nom = pag.ville');
      $query->innerJoin('orientation', 'ori', 'ori.ville_id = upv.id');
      $query->innerJoin('EPCI_', 'epci', 'epci.id = ori.EPCI_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');
      $query->innerJoin('admin_coordonnee', 'ac', 'pag.id = ac.object_id');

      $query->fields('pag', ['adresse', 'nom', 'telephone', 'code_postal', 'ville', 'email', 'contact']);
      $query->addField('pad', 'tel_mobile');
      $query->addField('pad', 'complement');
      $query->addField('pr', 'complement', 'complement_identification');
      $query->addExpression("SUBSTRING(pag.code_postal, 1, 2)", 'departement');
      $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');

      $query->condition('ps.enabled', 1)
        ->condition('ac.type', '0 | %', 'LIKE')
        ->condition('p.type', $type . '%', 'LIKE')
        ->condition('epci.id', $epciid)
        ->orderBy('pag.nom', 'ASC');

      return $this->formatResults($query->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getPartenaireByEpci: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  protected function getPartenaireByType(int $type): array {
    try {
      $query = $this->database->select('partenaire_', 'p');

      $query->innerJoin('partenaire_identification', 'pi', 'p.partenaire_identification_id = pi.id');
      $query->innerJoin('partenaire_adresse', 'pad', 'p.partenaire_adresse_id = pad.id');
      $query->innerJoin('partenaire__partenaire_agence', 'ppa', 'p.id = ppa.partenaire__id');
      $query->innerJoin('partenaire_agence', 'pag', 'pag.id = ppa.partenaire_agence_id');
      $query->innerJoin('partenaire_statut', 'ps', 'p.partenaire_statut_id = ps.id');
      $query->leftJoin('partenaire_option_renovateur', 'pr', 'p.partenaire_option_renovateur_id = pr.id');
      $query->innerJoin('admin_coordonnee', 'ac', 'pag.id = ac.object_id');

      $query->fields('pag', ['adresse', 'nom', 'telephone', 'code_postal', 'ville', 'email', 'contact']);
      $query->addField('pad', 'tel_mobile');
      $query->addField('pad', 'complement');
      $query->addField('pr', 'complement', 'complement_identification');
      $query->addExpression("SUBSTRING(pag.code_postal, 1, 2)", 'departement');
      $query->addExpression("SUBSTR(p.type, 1, 1)", 'type');

      $query->condition('ps.enabled', 1)
        ->condition('ac.type', '0 | %', 'LIKE')
        ->condition('p.type', $type . '%', 'LIKE')
        ->orderBy('pag.nom', 'ASC');

      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      return $this->formatResults($results);
    }
    catch (\Exception $e) {
      $this->logger->error('Error in getPartenaireByType: ' . $e->getMessage());
      return [];
    }
  }

  protected function formatResults(array $results): array {
    if (empty($results)) {
      return [];
    }

    $dept = $this->getDepartments();
    $formatted = [];

    foreach ($results as $record) {
      $formatted[] = [
        'ADRESSE' => $record['adresse'] ?? '',
        'NOM' => $record['nom'] ?? '',
        'CONTACT' => $record['contact'] ?? '',
        'TELEPHONE' => $record['telephone'] ?? '',
        'DEPARTEMENT' => $dept[$record['departement']] ?? 'Unknown',
        'CODE_POSTAL' => $record['code_postal'] ?? '',
        'VILLE' => $record['ville'] ?? '',
        'EMAIL' => $record['email'] ?? '',
        'COMPLEMENT_IDENTIFICATION' => $record['complement_identification'] ?? '',
      ];
    }

    return $formatted;
  }

  protected function getDepartments(): array {
    $result = $this->database->select('up_departement', 'ud')
      ->fields('ud', ['departement_code', 'departement_nom'])
      ->execute()
      ->fetchAllKeyed(0, 1);

    $depts = [];
    foreach ($result as $code => $nom) {
      $depts[$code] = $code . ' - ' . $nom;
    }
    return $depts;
  }

  protected function generatePdf(array $byDept, int $type): string {
    // Create PDF using TCPDF via Composer autoloading.
    $pdf = new \TCPDF('L', 'mm', 'A4', TRUE, 'UTF-8', FALSE);
    $pdf->SetCreator('Region Normandie');

    if ($type == 0) {
      $pdf->SetTitle('Les auditeurs');
      $pdf->SetSubject('Liste des auditeurs conventionnés par la région');
      $label2 = 'Liste des auditeurs conventionnés par la région';
      $label = "Chèque éco-énergie Normandie : \"Aide Audit énergétique et scénarios\"";
      $colspan = 6;
    }
    else {
      $pdf->SetTitle('Les rénovateurs');
      $pdf->SetSubject('Liste des rénovateurs conventionnés par la région');
      $label2 = 'Liste des Rénovateurs BBC Normandie';
      $label = 'Chèque éco-énergie Normandie : Aide "Travaux"';
      $colspan = 7;
    }

    $pdf->setPrintHeader(FALSE);
    $pdf->setPrintFooter(FALSE);
    $pdf->SetDefaultMonospacedFont(\PDF_FONT_MONOSPACED);
    $pdf->SetMargins(\PDF_MARGIN_LEFT, 10, \PDF_MARGIN_RIGHT, TRUE);
    $pdf->SetAutoPageBreak(TRUE, \PDF_MARGIN_BOTTOM);
    $pdf->setImageScale(\PDF_IMAGE_SCALE_RATIO);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->AddPage();

    // Header with logo.
    $themePath = $this->extensionPathResolver->getPath('theme', 'normandie');
    $logoPath = $themePath . '/images/logo_habitat_energie_chq.jpg';

    // Convert relative path to absolute.
    if (!file_exists($logoPath)) {
      $logoPath = DRUPAL_ROOT . '/' . $logoPath;
    }

    $html = <<<EOF
<table border="0" align="center" style="padding-top:30px;">
<tr height="150">
    <td colspan="2" align="center" valign="middle">
        <img src="$logoPath" />
    </td>
    <td colspan="$colspan" align="center" valign="middle">
        <br><br><br><strong>$label</strong><br><br>$label2
    </td>
</tr>
</table>
<br>
EOF;

    $pdf->writeHTML($html, TRUE, FALSE, TRUE, FALSE, '');
    $pdf->SetFillColor(243, 131, 54);

    // Table headers.
    if ($type == 0) {
      $pdf->MultiCell(75, 16, 'Structure', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(25, 16, 'Contact', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(30, 16, 'Téléphone', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(75, 16, 'Mail', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(25, 16, 'Adresse', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(15, 16, 'CP', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
      $pdf->MultiCell(35, 16, 'Ville', 1, 'C', 1, 1, '', '', TRUE, 0, FALSE, TRUE, 16, 'M');
    }
    else {
      $pdf->MultiCell(40, 16, 'Structure', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(25, 16, 'Contact', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(30, 16, 'Téléphone', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(75, 16, 'Mail', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(25, 16, 'Adresse', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(15, 16, 'CP', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(35, 16, 'Ville', 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
      $pdf->MultiCell(35, 16, 'Informations complémentaires', 1, 'C', 1, 1, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
    }

    // Data rows.
    $i = 1;
    $p = 1;
    foreach ($byDept as $dept => $items) {
      $pdf->SetFillColor(133, 194, 65);
      $pdf->MultiCell(280, 8, $dept, 1, 'L', 1, 1, '', '', TRUE, 0, FALSE, TRUE, 8, 'M');

      foreach ($items as $item) {
        $i++;
        $pdf->SetFillColor(255, 255, 255);

        if ($type == 0) {
          $pdf->MultiCell(75, 16, $item['NOM'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(25, 16, $item['CONTACT'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(30, 16, $item['TELEPHONE'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(75, 16, $item['EMAIL'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(25, 16, $item['ADRESSE'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(15, 16, $item['CODE_POSTAL'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);
          $pdf->MultiCell(35, 16, $item['VILLE'], 1, 'C', 1, 1, '', '', TRUE, 0, FALSE, TRUE, 16, 'M', TRUE);

          if ($p == 1 && $i == 6) {
            $pdf->AddPage();
            $i = 1;
            $p++;
          }
          elseif ($i == 10) {
            $pdf->AddPage();
            $i = 1;
            $p++;
          }
        }
        else {
          $pdf->MultiCell(40, 24, $item['NOM'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(25, 24, $item['CONTACT'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(30, 24, $item['TELEPHONE'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(75, 24, $item['EMAIL'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(25, 24, $item['ADRESSE'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(15, 24, $item['CODE_POSTAL'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(35, 24, $item['VILLE'], 1, 'C', 1, 0, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);
          $pdf->MultiCell(35, 24, $item['COMPLEMENT_IDENTIFICATION'] ?? '', 1, 'C', 1, 1, '', '', TRUE, 0, FALSE, TRUE, 24, 'M', TRUE);

          if ($p == 1 && $i == 5) {
            $pdf->AddPage();
            $i = 1;
            $p++;
          }
          elseif ($i == 7) {
            $pdf->AddPage();
            $i = 1;
            $p++;
          }
        }
      }
    }

    // Remove last page if empty.
    if ($i == 1) {
      $pdf->DeletePage($p);
    }

    return $pdf->Output('', 'S');
  }

  protected function getFilename(int $type): string {
    if ($type == 0) {
      return 'auditeurs_' . date('d_m_Y');
    }
    return 'renovateurs_' . date('d_m_Y');
  }

  protected function validateExportParameters(int $type, array $params): bool {
    if ($type < 0 || $type > 1) {
      return FALSE;
    }

    if (!empty($params['counties_codes']) && !$this->validator->validateDepartement($params['counties_codes'])) {
      return FALSE;
    }

    if (!empty($params['postal_codes']) && !$this->validator->validateCodePostal($params['postal_codes'])) {
      return FALSE;
    }

    if (!empty($params['epci']) && !$this->validator->validateEpciId((int) $params['epci'])) {
      return FALSE;
    }

    return TRUE;
  }

}
