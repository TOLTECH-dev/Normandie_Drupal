/**
 * @file
 * Chiffrescles functionality.
 */

(function ($) {
  'use strict';

  /**
   * Display number of ongoing dossiers.
   *
   * @returns {void}
   */
  function dossierEncours() {
    $.ajax({
      url: '/get_nombre_dossiers',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques.
   *
   * @returns {void}
   */
  function nombresdeCheque() {
    $.ajax({
      url: '/get_nombre_cheques',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Populate cheque type dropdown.
   *
   * @returns {void}
   */
  function typeCheque() {
    $.ajax({
      url: '/get_type_cheques',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#type').append($('<option>').text(CHEQUE.type).attr('value', CHEQUE.valeur));
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of auditeurs.
   *
   * @returns {void}
   */
  function nombresdAuditeur() {
    $.ajax({
      url: '/get_nombre_auditeurs',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, AUDITEUR) {
            $('#auditeur').append(AUDITEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of renovateurs.
   *
   * @returns {void}
   */
  function nombresdeRenovateur() {
    $.ajax({
      url: '/get_nombre_renovateurs',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, RENOVATEUR) {
            $('#renovateur').append(RENOVATEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of permanences.
   *
   * @returns {void}
   */
  function nombresdePermanence() {
    $.ajax({
      url: '/get_nombre_permanences',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, PERMANENCE) {
            $('#permanence').append(PERMANENCE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Populate department code dropdown.
   *
   * @returns {void}
   */
  function listecodeDepartement() {
    $.ajax({
      url: '/get_liste_departement',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, county) {
            $('#counties_codes').append($('<option>').text(county.DEPARTEMENT).attr('value', county.DEPARTEMENT));
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Populate city dropdown.
   *
   * @returns {void}
   */
  function listeVille() {
    $.ajax({
      url: '/get_liste_ville',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, town) {
            $('#ville').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Populate EPCI dropdown.
   *
   * @returns {void}
   */
  function listeEpci() {
    $.ajax({
      url: '/get_liste_epci',
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, epci) {
            $('#epci').append($('<option>').text(epci.NOM_EPCI).attr('value', epci.ID_EPCI));
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Filter city dropdown by department code.
   *
   * @returns {void}
   */
  function filtreVillepardep() {
    var codepostale = $('#counties_codes').val();
    var town = document.getElementById('ville');

    town.length = 0;
    $('#ville').append($('<option>').text('Ville').attr('value', ''));
    $.ajax({
      url: '/get_liste_ville_par_departement/' + codepostale,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $.each(json, function (j, value) {
          $.each(value, function (i, town) {
            $('#ville').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by department.
   *
   * @param {int} codedep
   *   The department code.
   *
   * @returns {void}
   */
  function dossierEncoursParcodedepartement(codedep) {
    $.ajax({
      url: '/get_nombre_dossiers_par_codedepartement/' + codedep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by city.
   *
   * @param {int} insee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function dossierEncoursParville(insee) {
    $.ajax({
      url: '/get_nombre_dossiers_par_ville/' + insee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by EPCI.
   *
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function dossierEncoursParepci(idepci) {
    $.ajax({
      url: '/get_nombre_dossiers_par_epci/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by department.
   *
   * @param {int} codedep
   *   The department code.
   *
   * @returns {void}
   */
  function nombresdeChequeParcodedepartement(codedep) {
    $.ajax({
      url: '/get_nombre_cheques_par_codedepartement/' + codedep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of auditeurs by department.
   *
   * @param {int} codedep
   *   The department code.
   *
   * @returns {void}
   */
  function getNombreAuditeursparCodedepartement(codedep) {
    $.ajax({
      url: '/get_nombre_auditeurs_par_codedepartement/' + codedep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#auditeur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, AUDITEUR) {
            $('#auditeur').append(AUDITEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of renovateurs by department.
   *
   * @param {int} codedep
   *   The department code.
   *
   * @returns {void}
   */
  function getNombreRenovateursparCodedepartement(codedep) {
    $.ajax({
      url: '/get_nombre_renovateurs_par_codedepartement/' + codedep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#renovateur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, RENOVATEUR) {
            $('#renovateur').append(RENOVATEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of permanences by department.
   *
   * @param {int} codedep
   *   The department code.
   *
   * @returns {void}
   */
  function getNombrePermanencesparCodedepartement(codedep) {
    $.ajax({
      url: '/get_nombre_permanences_par_codedepartement/' + codedep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#permanence').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, PERMANENCE) {
            $('#permanence').append(PERMANENCE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of auditeurs by city.
   *
   * @param {int} insee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function getNombreAuditeursparville(insee) {
    $.ajax({
      url: '/get_nombre_auditeurs_par_ville/' + insee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#auditeur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, AUDITEUR) {
            $('#auditeur').append(AUDITEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of renovateurs by city.
   *
   * @param {int} insee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function getNombreRenovateursparVille(insee) {
    $.ajax({
      url: '/get_nombre_renovateurs_par_ville/' + insee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#renovateur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, RENOVATEUR) {
            $('#renovateur').append(RENOVATEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by city.
   *
   * @param {int} insee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function nombresdeChequeParville(insee) {
    $.ajax({
      url: '/get_nombre_cheques_par_ville/' + insee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of permanences by city.
   *
   * @param {int} codeinsee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function getNombrePermanencesparVille(codeinsee) {
    $.ajax({
      url: '/get_nombre_permanences_par_ville/' + codeinsee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#permanence').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, PERMANENCE) {
            $('#permanence').append(PERMANENCE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by EPCI.
   *
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function nombresdeChequeParepci(idepci) {
    $.ajax({
      url: '/get_nombre_cheques_par_epci/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of auditeurs by EPCI.
   *
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function getNombreAuditeursparEpci(idepci) {
    $.ajax({
      url: '/get_nombre_auditeurs_par_epci/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#auditeur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, AUDITEUR) {
            $('#auditeur').append(AUDITEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of renovateurs by EPCI.
   *
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function getNombreRenovateursparEpci(idepci) {
    $.ajax({
      url: '/get_nombre_renovateurs_par_epci/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#renovateur').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, RENOVATEUR) {
            $('#renovateur').append(RENOVATEUR.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of permanences by EPCI.
   *
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function getNombrePermanencesparEpci(idepci) {
    $.ajax({
      url: '/get_nombre_permanences_par_epci/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#permanence').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, PERMANENCE) {
            $('#permanence').append(PERMANENCE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by cheque type.
   *
   * @param {int} typecheque
   *   The cheque type.
   *
   * @returns {void}
   */
  function dossierEncoursPartypecheque(typecheque) {
    $.ajax({
      url: '/get_nombre_dossiers_par_type_cheque/' + typecheque,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by cheque type.
   *
   * @param {int} typecheque
   *   The cheque type.
   *
   * @returns {void}
   */
  function nombresdeChequePartypecheque(typecheque) {
    $.ajax({
      url: '/get_nombre_cheques_par_type_cheque/' + typecheque,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by cheque type and EPCI.
   *
   * @param {int} type
   *   The cheque type.
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function dossierEncoursPartypechequeETepci(type, idepci) {
    $.ajax({
      url: '/get_nombre_dossiers_par_type_cheque_et_epci/' + type + '/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by cheque type and EPCI.
   *
   * @param {int} typecheque
   *   The cheque type.
   * @param {int} idepci
   *   The EPCI ID.
   *
   * @returns {void}
   */
  function nombresdeChequePartypechequeETepci(typecheque, idepci) {
    $.ajax({
      url: '/get_nombre_cheques_par_type_cheque_et_epci/' + typecheque + '/' + idepci,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by cheque type and department.
   *
   * @param {int} type
   *   The cheque type.
   * @param {int} codeDep
   *   The department code.
   *
   * @returns {void}
   */
  function dossierEncoursPartypechequeETCodeDep(type, codeDep) {
    $.ajax({
      url: '/get_nombre_dossiers_par_type_cheque_et_codedep/' + type + '/' + codeDep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by cheque type and department.
   *
   * @param {int} typecheque
   *   The cheque type.
   * @param {int} codeDep
   *   The department code.
   *
   * @returns {void}
   */
  function nombresdeChequePartypechequeETCodeDep(typecheque, codeDep) {
    $.ajax({
      url: '/get_nombre_cheques_par_type_cheque_et_codedep/' + typecheque + '/' + codeDep,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of dossiers by cheque type and city.
   *
   * @param {int} type
   *   The cheque type.
   * @param {int} codeinsee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function dossierEncoursPartypechequeETVille(type, codeinsee) {
    $.ajax({
      url: '/get_nombre_dossiers_par_type_cheque_et_ville/' + type + '/' + codeinsee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#dossier').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, DOSSIER) {
            $('#dossier').append(DOSSIER.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Display number of cheques by cheque type and city.
   *
   * @param {int} typecheque
   *   The cheque type.
   * @param {int} codeinsee
   *   The INSEE code.
   *
   * @returns {void}
   */
  function nombresdeChequePartypechequeETVille(typecheque, codeinsee) {
    $.ajax({
      url: '/get_nombre_cheques_par_type_cheque_et_ville/' + typecheque + '/' + codeinsee,
      type: 'GET',
      dataType: 'json',
      success: function (json) {
        $('#cheque').empty();
        $.each(json, function (j, value) {
          $.each(value, function (i, CHEQUE) {
            $('#cheque').append(CHEQUE.Nombre);
          });
        });
      },
      error: function () {
        console.log('Erreur serveur');
      },
    });
  }

  /**
   * Listen for department code selection change.
   */
  $(function () {
    $('#counties_codes').change(function () {
      $('#ville').find('option:first').prop('selected', 'selected');
      $('#epci').find('option:first').prop('selected', 'selected');

      if ($('#counties_codes').val() !== '' && $('#ville').val() === '' && $('#epci').val() === '' && $('#type').val() === '') {
        var codedep = $('#counties_codes').val();
        filtreVillepardep();

        dossierEncoursParcodedepartement(codedep);
        nombresdeChequeParcodedepartement(codedep);
        getNombreAuditeursparCodedepartement(codedep);
        getNombreRenovateursparCodedepartement(codedep);
        getNombrePermanencesparCodedepartement(codedep);
      }

      if ($('#epci').val() === '' && $('#type').val() !== '' && $('#counties_codes').val() !== '' && $('#ville').val() === '') {
        var type = $('#type').val();
        var codedep = $('#counties_codes').val();
        filtreVillepardep();

        dossierEncoursPartypechequeETCodeDep(type, codedep);
        nombresdeChequePartypechequeETCodeDep(type, codedep);
        getNombreAuditeursparCodedepartement(codedep);
        getNombreRenovateursparCodedepartement(codedep);
        getNombrePermanencesparCodedepartement(codedep);
      }

      if ($('#epci').val() === '' && $('#type').val() === '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        $('#cheque').empty();
        $('#dossier').empty();
        $('#auditeur').empty();
        $('#renovateur').empty();
        $('#permanence').empty();
        dossierEncours();
        nombresdeCheque();
        nombresdAuditeur();
        nombresdeRenovateur();
        nombresdePermanence();
      }
    });
  });

  /**
   * Listen for city selection change.
   */
  $(function () {
    $('#ville').change(function () {
      $('#epci').find('option:first').prop('selected', 'selected');
      $('#counties_codes').find('option:first').prop('selected', 'selected');

      if ($('#ville').val() !== '' && $('#epci').val() === '' && $('#type').val() === '' || $('#counties_codes').val() === '') {
        var insee = $('#ville').val();
        dossierEncoursParville(insee);
        nombresdeChequeParville(insee);
        getNombreAuditeursparville(insee);
        getNombreRenovateursparVille(insee);
        getNombrePermanencesparVille(insee);
      }

      if ($('#epci').val() === '' && $('#type').val() !== '' && $('#counties_codes').val() === '' && $('#ville').val() !== '') {
        var type = $('#type').val();
        var insee = $('#ville').val();

        dossierEncoursPartypechequeETVille(type, insee);
        nombresdeChequePartypechequeETVille(type, insee);
        getNombreAuditeursparville(insee);
        getNombreRenovateursparVille(insee);
        getNombrePermanencesparVille(insee);
      }

      if ($('#epci').val() === '' && $('#type').val() === '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        $('#cheque').empty();
        $('#dossier').empty();
        $('#auditeur').empty();
        $('#renovateur').empty();
        $('#permanence').empty();
        dossierEncours();
        nombresdeCheque();
        nombresdAuditeur();
        nombresdeRenovateur();
        nombresdePermanence();
      }
    });
  });

  /**
   * Listen for EPCI selection change.
   */
  $(function () {
    $('#epci').change(function () {
      $('#ville').find('option:first').prop('selected', 'selected');
      $('#counties_codes').find('option:first').prop('selected', 'selected');

      if ($('#epci').val() !== '' && $('#type').val() === '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        var idepci = $('#epci').val();
        dossierEncoursParepci(idepci);
        nombresdeChequeParepci(idepci);
        getNombreAuditeursparEpci(idepci);
        getNombreRenovateursparEpci(idepci);
        getNombrePermanencesparEpci(idepci);
      }

      if ($('#epci').val() !== '' && $('#type').val() !== '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        var type = $('#type').val();
        var idepci = $('#epci').val();
        dossierEncoursPartypechequeETepci(type, idepci);
        nombresdeChequePartypechequeETepci(type, idepci);
        getNombreAuditeursparEpci(idepci);
        getNombreRenovateursparEpci(idepci);
        getNombrePermanencesparEpci(idepci);
      }

      if ($('#epci').val() === '' && $('#type').val() === '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        $('#cheque').empty();
        $('#dossier').empty();
        $('#auditeur').empty();
        $('#renovateur').empty();
        $('#permanence').empty();
        dossierEncours();
        nombresdeCheque();
        nombresdAuditeur();
        nombresdeRenovateur();
        nombresdePermanence();
      }
    });
  });

  /**
   * Listen for cheque type selection change.
   */
  $(function () {
    $('#type').change(function () {
      $('#epci').find('option:first').prop('selected', 'selected');
      $('#ville').find('option:first').prop('selected', 'selected');
      $('#counties_codes').find('option:first').prop('selected', 'selected');

      if ($('#type').val() !== '' && $('#counties_codes').val() === '' && $('#ville').val() === '' && $('#epci').val() === '') {
        var type = $('#type').val();
        dossierEncoursPartypecheque(type);
        nombresdeChequePartypecheque(type);
        $('#cheque').empty();
        $('#dossier').empty();
        $('#auditeur').empty();
        $('#renovateur').empty();
        $('#permanence').empty();
        nombresdAuditeur();
        nombresdeRenovateur();
        nombresdePermanence();
      }

      if ($('#epci').val() === '' && $('#type').val() === '' && $('#counties_codes').val() === '' && $('#ville').val() === '') {
        $('#cheque').empty();
        $('#dossier').empty();
        $('#auditeur').empty();
        $('#renovateur').empty();
        $('#permanence').empty();
        dossierEncours();
        nombresdeCheque();
        nombresdAuditeur();
        nombresdeRenovateur();
        nombresdePermanence();
      }
    });
  });

  /**
   * Listen for reset button click.
   */
  $(function () {
    $('#reset').click(function () {
      $('#dossier').empty();
      $('#cheque').empty();
      $('#auditeur').empty();
      $('#renovateur').empty();
      $('#permanence').empty();
      $('#type').val('');
      $('#epci').val('');
      $('#ville').val('');
      $('#counties_codes').val('');
      dossierEncours();
      nombresdeCheque();
      nombresdAuditeur();
      nombresdeRenovateur();
      nombresdePermanence();
    });
  });

  /**
   * Initialize on page load.
   */
  $(function () {
    dossierEncours();
    nombresdeCheque();
    typeCheque();
    nombresdAuditeur();
    nombresdeRenovateur();
    nombresdePermanence();
    listecodeDepartement();
    listeVille();
    listeEpci();
  });

}(jQuery));
