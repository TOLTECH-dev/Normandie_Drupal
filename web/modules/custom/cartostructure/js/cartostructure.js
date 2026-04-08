(function ($, Drupal, once) {
  'use strict';

  Drupal.behaviors.cartostructure = {
    attach: function (context, settings) {
      once('cartostructure', '#map', context).forEach(function (element) {
        var map;
        var markers = new L.MarkerClusterGroup({
          iconCreateFunction: function (cluster) {
            return L.divIcon({
              html: cluster.getAllChildMarkers().length,
              className: 'mycluster',
              iconSize: L.point(40, 40)
            });
          }
        });

        var codePostalEvent = [];
        var mapmargin = 0;

        // Initialize map dimensions.
        $('#map').css('height', (($(window).height() * 3 / 4) - mapmargin));
        $('#map').css('width', '100%');

        $(window).on('resize', resize);
        resize();

        function resize() {
          if ($(window).width() >= 980) {
            $('#map').css('height', (($(window).height() * 3 / 4) - mapmargin));
            $('#map').css('width', '100%');
          }
          else {
            $('#map').css('height', (($(window).height() * 3 / 4) - (mapmargin + 12)));
          }
        }

        function loadMap() {
          var cloudmadeUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
          var cloudmadeAttribution = 'Map data &copy; 2024 <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
          var cloudmade = new L.TileLayer(cloudmadeUrl, {
            minZoom: 2,
            maxZoom: 25,
            attribution: cloudmadeAttribution
          });
          var latlng = new L.LatLng(49.183333, -0.35);
          map = new L.Map('map', {
            center: latlng,
            zoom: 8,
            layers: [cloudmade],
            minZoom: 8,
            maxZoom: 17
          });
          resize();
          populateStructures();
          populateCountyCode();
          populatePostalCode();
          populateTowns();
          populateEpci();
          showStructures({});
        }

        function showStructures(options) {
          var url = '/cartostructure/get-structures';

          if (options.structure) {
            if (options.departement) {
              url = '/cartostructure/get-permanences-par-structure-et-departement/' + options.structure + '/' + options.departement;
            }
            else if (options.cp) {
              url = '/cartostructure/get-permanences-par-structure-et-code-postal/' + options.structure + '/' + options.cp;
            }
            else if (options.ville) {
              url = '/cartostructure/get-permanences-par-structure-et-ville/' + options.structure + '/' + options.ville;
            }
            else if (options.epci) {
              url = '/cartostructure/get-permanences-par-structure-et-epci/' + options.structure + '/' + options.epci;
            }
            else {
              url = '/cartostructure/get-permanences-par-structure/' + options.structure;
            }
          }
          else if (options.departement) {
            url = '/cartostructure/get-permanences-par-departement/' + options.departement;
          }
          else if (options.cp) {
            url = '/cartostructure/get-permanences-par-code-postal/' + options.cp;
          }
          else if (options.ville) {
            url = '/cartostructure/get-permanences-par-ville/' + options.ville;
          }
          else if (options.epci) {
            url = '/cartostructure/get-permanences-par-epci/' + options.epci;
          }

          $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.structures) {
                $.each(json.structures, function (i, data) {
                  if (data.ADRESSE && data.CODE_POSTAL) {
                    populateStructure(data);
                  }
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function populateStructure(rawData) {
          var geojsonMarkerOptions = L.icon({
            iconUrl: '/themes/custom/normandie/images/new_image/petale/petale_42922a.svg',
            iconSize: [30, 30]
          });

          var message = '';
          if (rawData.NOM_PERMANENCE) {
            message += '<p><b>' + rawData.NOM_PERMANENCE + '</b></p>';
          }
          message += '<ul>';
          if (rawData.ADRESSE) {
            message += '<li>' + rawData.ADRESSE + '</li>';
          }
          if (rawData.CODE_POSTAL || rawData.VILLE) {
            message += '<li>';
            if (rawData.CODE_POSTAL) {
              message += rawData.CODE_POSTAL + ' ';
            }
            if (rawData.VILLE) {
              message += rawData.VILLE;
            }
            message += '</li>';
          }
          if (rawData.TELEPHONE) {
            message += '<li>' + rawData.TELEPHONE + '</li>';
          }
          if (rawData.JOUR_OUVERTURE) {
            message += '<li>' + rawData.JOUR_OUVERTURE + '</li>';
          }
          if (rawData.HORAIRE) {
            message += '<li>' + rawData.HORAIRE + '</li>';
          }
          message += '</ul>';

          var marker = L.marker(L.latLng(rawData.LAT, rawData.LONG), {icon: geojsonMarkerOptions});
          marker.bindPopup(message);
          markers.addLayer(marker);
          map.addLayer(markers);
        }

        function populateStructures() {
          $.ajax({
            url: '/cartostructure/get-liste-structures',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.structures) {
                $.each(json.structures, function (i, structure) {
                  $('#structures').append($('<option>').text(structure.NOM_STRUCTURE).attr('value', structure.ID_STRUCTURE));
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function populateCountyCode() {
          $.ajax({
            url: '/cartostructure/get-liste-departement',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.departements) {
                $.each(json.departements, function (i, county) {
                  $('#counties_codes').append($('<option>').text(county.NOM_DEPARTEMENT).attr('value', county.CODE_DEPARTEMENT));
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function populatePostalCode() {
          $.ajax({
            url: '/cartostructure/get-liste-code-postal',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.codes_postaux) {
                $.each(json.codes_postaux, function (i, postalCode) {
                  $('#postal_codes').append($('<option>').text(postalCode.CODE_POSTAL).attr('value', postalCode.CODE_POSTAL));
                  codePostalEvent.push(postalCode.CODE_POSTAL);
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function populateTowns() {
          $.ajax({
            url: '/cartostructure/get-liste-ville',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.villes) {
                $.each(json.villes, function (i, town) {
                  var displayName = town.NOM + ' (' + town.CODE_POSTAL + ')';
                  $('#towns').append($('<option>').text(displayName).attr('value', town.CODE_INSEE));
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function populateEpci() {
          $.ajax({
            url: '/cartostructure/get-liste-epci',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
              if (json.epci) {
                $.each(json.epci, function (i, epci) {
                  $('#epci').append($('<option>').text(epci.NOM_EPCI).attr('value', epci.ID_EPCI));
                });
              }
            },
            error: function () {
              console.log('Erreur serveur');
            }
          });
        }

        function removeMarker() {
          markers.clearLayers();
        }

        function filtreCpByDep() {
          var valcpshort = $('#counties_codes').val();
          var taille = codePostalEvent.length;
          var cp = document.getElementById('postal_codes');
          cp.length = 0;
          $('#postal_codes').append($('<option>').text('Code postal').attr('value', ''));
          for (var i = 0; i < taille; i++) {
            if (valcpshort === codePostalEvent[i].substr(0, 2)) {
              $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
            }
          }
        }

        function reinitialiserCp() {
          var taille = codePostalEvent.length;
          var cp = document.getElementById('postal_codes');
          cp.length = 0;
          $('#postal_codes').append($('<option>').text('Code postal').attr('value', ''));
          for (var i = 0; i < taille; i++) {
            $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
          }
        }

        function reinitialiserTowns() {
          var towns = document.getElementById('towns');
          towns.length = 0;
          $('#towns').append($('<option>').text('Ville').attr('value', '').attr('selected', 'selected'));
          populateTowns();
        }

        // Event handlers.
        $('#structures').on('change', function () {
          $('#counties_codes').find('option:first').prop('selected', 'selected');
          $('#postal_codes').find('option:first').prop('selected', 'selected');
          $('#towns').find('option:first').prop('selected', 'selected');
          $('#epci').find('option:first').prop('selected', 'selected');
        });

        $('#counties_codes').on('change', function () {
          if ($(this).val() !== '') {
            $('#postal_codes').find('option:first').prop('selected', 'selected');
            $('#towns').find('option:first').prop('selected', 'selected');
            $('#epci').find('option:first').prop('selected', 'selected');
            filtreCpByDep();
          }
        });

        $('#postal_codes').on('change', function () {
          if ($(this).val() !== '') {
            $('#counties_codes').find('option:first').prop('selected', 'selected');
            $('#towns').find('option:first').prop('selected', 'selected');
            $('#epci').find('option:first').prop('selected', 'selected');
          }
        });

        $('#towns').on('change', function () {
          if ($(this).val() !== '') {
            $('#counties_codes').find('option:first').prop('selected', 'selected');
            $('#postal_codes').find('option:first').prop('selected', 'selected');
            $('#epci').find('option:first').prop('selected', 'selected');
          }
        });

        $('#epci').on('change', function () {
          if ($(this).val() !== '') {
            $('#counties_codes').find('option:first').prop('selected', 'selected');
            $('#postal_codes').find('option:first').prop('selected', 'selected');
            $('#towns').find('option:first').prop('selected', 'selected');
          }
        });

        $('#search').on('click', function (ev) {
          ev.preventDefault();
          removeMarker();
          showStructures({
            epci: $('#epci').val(),
            structure: $('#structures').val(),
            ville: $('#towns').val(),
            cp: $('#postal_codes').val(),
            departement: $('#counties_codes').val()
          });
        });

        $('#reset').on('click', function (ev) {
          ev.preventDefault();
          removeMarker();
          $('#postal_codes').find('option:first').prop('selected', 'selected');
          $('#counties_codes').find('option:first').prop('selected', 'selected');
          $('#towns').find('option:first').prop('selected', 'selected');
          $('#structures').find('option:first').prop('selected', 'selected');
          $('#epci').find('option:first').prop('selected', 'selected');
          reinitialiserCp();
          reinitialiserTowns();
          showStructures({});
        });

        // Initialize map.
        loadMap();
      });
    }
  };

})(jQuery, Drupal, once);
