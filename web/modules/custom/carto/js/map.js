(function ($, Drupal, once) {
    'use strict';

    var map;
    var layerGroup;
    var markers = new L.MarkerClusterGroup({
        iconCreateFunction: function (cluster) {
            var markersarray = cluster.getAllChildMarkers();
            return L.divIcon({ html: cluster.getAllChildMarkers().length, className: 'mycluster', iconSize: L.point(40, 40) });
        }
    });
    var listeDepartement;
    var mapmargin = 0;
    var codePostalEvent = [];
    var dataExport = "";

    function resize()
    {
        if ($(window).width() >= 980)
        {
            $('#map').css("height", (($(window).height() * 3 / 4) - mapmargin));
            $('#map').css("width", "100%");
            //$('#map').css("margin-top", 50);
        } else
        {
            $('#map').css("height", (($(window).height() * 3 / 4) - (mapmargin + 12)));
            //$('#map').css("margin-top", -21);
        }

        // Invalider la taille de la carte après le redimensionnement
        if (map) {
            setTimeout(function() {
                map.invalidateSize();
            }, 10);
        }
    }

    function load_map()
    {
            var cloudmadeUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            var cloudmadeAttribution = 'Map data &copy; 2017 <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
            var cloudmade = new L.TileLayer(cloudmadeUrl, {minZoom: 2,maxZoom: 25, attribution: cloudmadeAttribution});
            var latlng = new L.LatLng(49.183333, -0.35);
            map = new L.Map('map', {center: latlng, zoom: 8, layers: [cloudmade], minZoom: 8, maxZoom: 17});
            
            resize();
            
            // Fix pour Firefox : invalider la taille après un court délai
            setTimeout(function() {
                if (map) {
                    map.invalidateSize();
                }
            }, 100);
            
            populate_postal_code();
            populate_county_code();
            populate_towns();
            populate_epci();
            //showPartners({});
            showPartnersBytype({type: $("#partners").val()});
    }

    function showPartners(options) 
    {
        if (options.CODE_POSTAL) 
        {
            $.ajax({
                url: '/get_partenaires_par_codepostal/' + options.CODE_POSTAL,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {
                             populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else if (options.ville) 
        {
            $.ajax({
                url: '/get_partenaires_par_ville/' + options.ville,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                                                      
                             populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else if (options.type) 
        {
            $.ajax({
                url: '/get_partenaires_par_type/' + options.type,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({business: data.THEMA_PRESTA}, data);                        
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else if (options.departement)
        {
            $.ajax({
                url: '/get_partenaires_par_departement/' + options.departement,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else 
        {
            $.ajax({
                url: '/get_partenaires',
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {  
                                populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        }
    }

    function showPartnerByEPCI(options)
    {
        if (options.epci) 
        {
            $.ajax({
                url: '/get_liste_partenaires_par_epci/' + options.epci,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur epci');
                }
            });
        }
    }

    /**
     * 
     * @param {type} options
     * @param {type} raw_data
     * @returns {undefined}
     */
    function populatePartners(options, raw_data)
    {
        var geojsonMarkerOptions;
        var message;
        
        switch (options.business) 
        {
            case "1" :
                geojsonMarkerOptions = L.icon({
                    iconUrl: 'themes/custom/normandie/images/new_image/petale/petale_R.svg',
                     iconSize: [30,30],       });
                break;
            case "0" :
                geojsonMarkerOptions = L.icon({
                    iconUrl: 'themes/custom/normandie/images/new_image/petale/petale_A.svg',
                     iconSize: [30,30],       });
                break;
            default :
                geojsonMarkerOptions = L.icon({
                    iconUrl: 'themes/custom/normandie/images/new_image/petale/petale_ 8dccc4.svg',
                    iconSize: [30,30],        });
        }

        message = '';
        if (raw_data.RAISONSOCIALE != null)
            message += '<p><b>' + raw_data.RAISONSOCIALE + '</b></p>';

        message += '<ul>';

        if (raw_data.COMPLEMENT_IDENTIFICATION != null)
            message += '<li>' + raw_data.COMPLEMENT_IDENTIFICATION + '</li>';
        
        if (raw_data.ADRESSE != null)
            message += '<li>' + raw_data.ADRESSE + '</li>';

        if (raw_data.CODE_POSTAL  != null || raw_data.VILLE != null){
            message += '<li>';
            if(raw_data.CODE_POSTAL  != null)
            	message +=  raw_data.CODE_POSTAL + ' ';
            if(raw_data.VILLE  != null)
            	message +=  raw_data.VILLE;
        	message += '</li>';
    	}
        
        if (raw_data.TELEPHONE != null)
            message += '<li>' + raw_data.TELEPHONE + '</li>';

        if (raw_data.TEL_CONTACT != null)
            message += '<li>' + raw_data.TEL_CONTACT + '</li>';

         if (raw_data.EMAIL != null)
            message += '<li>' + raw_data.EMAIL + '</li>';

        if (raw_data.WWW != null)
            message += '<li>' + '<a target="_blank" href=' +raw_data.WWW + '>' + raw_data.WWW + '</a>' + '</li>';

        if (raw_data.COMPLEMENT != null)
            message += '<li>' + raw_data.COMPLEMENT + '</li>';
        message += '</ul>';

        var marker = L.marker(L.latLng(raw_data.LAT,raw_data.LONG), {icon: geojsonMarkerOptions});

		marker.bindPopup(message);
		markers.addLayer(marker);
		map.addLayer(markers); 
    }

    /**
     * 
     * @returns {undefined}
     * Rempli la liste des villes disponibles en base de données
     */
    function populate_towns() {
        $.ajax({
            url: '/get_liste_villepartenaire',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {

                        $('#ville').append($('<option>').text(town.VILLE).attr('value', town.CODEINSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    /**
     * 
     * @param {type} postal_code
     * @returns {undefined}
     * Rempli la liste des codes postaux disponibles en base de données
     */
    function populate_postal_code()
    {
        $.ajax({
            url: '/get_liste_code_postal',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, postal_code) {
                        $('#postal_codes').append($('<option>').text(postal_code.CODE_POSTAL).attr('value', postal_code.CODE_POSTAL));
                        codePostalEvent.push(postal_code.CODE_POSTAL);
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    /**
     * 
     * @returns {undefined}
     * Rempli la liste des codes département disponibles en base de données
     */
    function populate_county_code()
    {
        $.ajax({
            url: '/get_liste_departementpartenaire',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, county) {
                        $('#counties_codes').append($('<option>').text(county.CODE_DEPARTEMENT).attr('value', county.CODE_DEPARTEMENT));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    /**
     * @returns {undefined}
     * Rempli la liste des epci sur la région normandie
     */
    function populate_epci() 
    {
        $.ajax({
            url: '/get_liste_epcipartenaire',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, epci) {
                        $('#epci').append($('<option>').text(epci.epci).attr('value', epci.EPCI_ID));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    /**
     * Mise à jour des select
     */
    $(function () {
        $("#ville").change(function () {
            //removeMarker();
            if ($("#ville").val() != '') {
            	/*if ($('#partners').val() != '') {
            		showPartnerstypeville({type: $("#partners").val(), CODE_INSEE: $("#ville").val()});
            	}else{
            		showPartners({ville: $("#ville").val()});
            	}*/
                $('#postal_codes').find('option:first').prop('selected','selected');
                $("#counties_codes").find('option:first').prop('selected','selected');
                $("#epci").find('option:first').prop('selected','selected');
            }
        });
    });
    $(function () {
        $("#postal_codes").change(function () {
            //removeMarker();
            if ($("#postal_codes").val() != '') {                 
            	/*if ($('#partners').val() != '') {
            		showPartners2({type: $("#partners").val(), CODE_POSTAL: $("#postal_codes").val()});
            	}else{
            		showPartners({CODE_POSTAL: $("#postal_codes").val()});
            	}*/
                $('#ville').find('option:first').prop('selected','selected');
                $("#counties_codes").find('option:first').prop('selected','selected');
                filtreVille();
            }
        });
    });
    $(function () {
        $("#partners").change(function () {
            removeMarker();
            if ($('#ville').val() != '') {
                showPartnerstypeville({type: $("#partners").val(), CODE_INSEE: $("#ville").val()});
            } else if ($('#counties_codes').val() != '') {
                showPartners3({type: $("#partners").val(), CODE_POSTAL: $("#counties_codes").val()});
            } else if ($('#postal_codes').val() != '') {
                showPartners2({type: $("#partners").val(), CODE_POSTAL: $("#postal_codes").val()});
            } else if ($('#epci').val() != '') {
                showPartnersepciBytype({type: $("#partners").val(), epci: $("#epci").val()});
            } else {
                showPartnersBytype({type: $("#partners").val()});
            }
        });
    });
    
    $(function () {
        $("#counties_codes").change(function () {
            //removeMarker();
            if ($("#counties_codes").val() != '') {
            	/*if ($('#partners').val() != '') {
            		showPartners3({type: $("#partners").val(), CODE_POSTAL: $("#counties_codes").val()});
            	}else{
            		showPartners({departement: $("#counties_codes").val()});
            	}*/
                $('#ville').find('option:first').prop('selected','selected');
                $('#postal_codes').find('option:first').prop('selected','selected');
                filtreCp();
                filtreVilleBydep();
            }
        });
    });
    
    $(function () {
        $("#epci").change(function () {
            //removeMarker();
            if ($("#epci").val() != '') {
            	/*if ($('#partners').val() != '') {
            		showPartnersepciBytype({type: $("#partners").val(), epci: $("#epci").val()});
            	}else{
            		showPartnerByEPCI({epci: $("#epci").val()});
            	}*/
                $('#postal_codes').find('option:first').prop('selected','selected');
                $("#counties_codes").find('option:first').prop('selected','selected');
                $('#ville').find('option:first').prop('selected','selected');
            }
        });
    });

    $(function () {
        $("#search").click(function (ev) {

            ev.preventDefault();

            removeMarker();
            dataExport = "";

            if ($('#ville').val() != '') {
                showPartnerstypeville({type: $("#partners").val(), CODE_INSEE: $("#ville").val()});
            } else if ($('#counties_codes').val() != '') {
                showPartners3({type: $("#partners").val(), CODE_POSTAL: $("#counties_codes").val()});
            } else if ($('#postal_codes').val() != '') {
                showPartners2({type: $("#partners").val(), CODE_POSTAL: $("#postal_codes").val()});
            } else if ($('#epci').val() != '') {
                showPartnersepciBytype({type: $("#partners").val(), epci: $("#epci").val()});
            } else {
                showPartnersBytype({type: $("#partners").val()});
            }

        });
    });
    
    $(function () {
        $("#reset").click(function () {
            $('#postal_codes').find('option:first').prop('selected','selected');
            $("#counties_codes").find('option:first').prop('selected','selected');
            $('#ville').find('option:first').prop('selected','selected');
            $("#epci").find('option:first').prop('selected','selected');
            $('#checkBox').attr('checked', false);
            
            removeMarker();
            renitialiserCp();
            populate_towns();
            showPartnersBytype({type: $("#partners").val()});
        });
    });

    $(function () {
        $("#export").click(function (ev) {

            ev.preventDefault();

            $.ajax({
                url: '/get_export/' + $("#partners").val(),
                type: 'POST',
                data:{
                    postal_codes: $("#postal_codes").val(),
                    counties_codes: $("#counties_codes").val(),
                    ville: $("#ville").val(),
                    epci: $("#epci").val()
                },
                dataType: 'json',
                success: function (json) {
                    // Check if PDF data exists
                    if (json.pdf === null) {
                        // Error case: show error message
                        alert('Erreur : ' + (json.error || 'Impossible de générer le PDF'));
                        return;
                    }

                    var element = document.createElement('a');
                    element.setAttribute('href', 'data:application/octet-stream;base64,' +json.pdf);
                    element.setAttribute('download', json.filename);

                    element.style.display = 'none';
                    document.body.appendChild(element);

                    element.click();

                    document.body.removeChild(element);

                },
                error: function () {
                    console.log('Erreur serveur  000');
                }
            });
        });
    });
    
    function filtreCp()
    {
        var valcpshort = $("#counties_codes").val();
        var taille = codePostalEvent.length;
        var cp = document.getElementById('postal_codes');
        var resultat = [];
        cp.length = 0; //efface les data(s) de la sélection 
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {

            if (valcpshort === codePostalEvent[i].substr(0, 2))
                $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }

    function renitialiserCp() 
    {
        var taille = codePostalEvent.length;
        var cp = document.getElementById('postal_codes');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {
            $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }

    function filtreVille() {
        var codepostale = $('#postal_codes').val();
        var cp = document.getElementById('towns');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#ville').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_villebycp/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#ville').append($('<option>').text(town.VILLE).attr('value', town.CODE_POSTAL));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function filtreVilleBydep()
    {
        var codepostale = $('#counties_codes').val();
        var cp = document.getElementById('towns');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#ville').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_villebycpdep/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#ville').append($('<option>').text(town.VILLE).attr('value', town.CODE_POSTAL));
                        console.log(town.VILLE);
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }
    function filtreCp() 
    {
        var valcpshort = $("#counties_codes").val();
        var taille = codePostalEvent.length;
        var cp = document.getElementById('postal_codes');
        var resultat = [];
        cp.length = 0; //efface les data(s) de la sélection 
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {
            if (valcpshort === codePostalEvent[i].substr(0, 2))
                $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }
    
    function renitialiserCp()
    {
        var taille = codePostalEvent.length;
        var cp = document.getElementById('postal_codes');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {
            
            $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }

    function filtreVille() 
    {
        var codepostale = $('#postal_codes').val();
        var cp = document.getElementById('ville');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#ville').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_ville_par_codepostal_filtre/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#ville').append($('<option>').text(town.VILLE).attr('value', town.CODEINSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function filtreVilleBydep() 
    {
        var codepostale = $('#counties_codes').val();
        var cp = document.getElementById('ville');
        cp.length = 0; //efface les data(s) de la sélection 
        $('#ville').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_ville_par_codedep_filtre/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#ville').append($('<option>').text(town.VILLE).attr('value', town.CODEINSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }


    function showPartners2(options) 
    {
        if (options.type) {
            $.ajax({
                url: '/get_partenaires_par_type_codepostal/' + options.type + '/code_postal/' + options.CODE_POSTAL,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({ business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        }
    }

    function showPartnerstypeville(options) 
    {
        if (options.type) {
            $.ajax({
                url: '/get_partenaires_par_type_ville/' + options.type + '/code_postal/' + options.CODE_INSEE,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        }
    }

    function showPartners3(options)
    {
        if (options.type) {
            $.ajax({
                url: '/get_partenaires_par_type_codedepartement/' + options.type + '/code_postal/' + options.CODE_POSTAL,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                                populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        }
    }

    function showPartnersepciBytype(options) 
    {
        if (options.type && options.epci) {
            $.ajax({
                url: '/get_liste_partenaires_par_type_epci/' + options.type + '/' + options.epci,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                            populatePartners({business: data.THEMA_PRESTA}, data);                            
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur  000');
                }
            });
        }
    }
    
    function showPartnersBytype(options) 
    {
        if (options.type) {
            $.ajax({
                url: '/get_partenaires_par_typepartenaire/' + options.type,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                            
                            populatePartners({business: data.THEMA_PRESTA}, data);
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur  000');
                }
            });
        }
    }

    $(function () {
        $("#checkBox").change(function () {
            if ($('#checkBox').is(':checked')) {
                showDepartements();
            } else {
                map.removeLayer(listeDepartement);
            }
        });
    });
    
    /**
     * 
     * @returns {undefined}
     * affiche les départements
     */
    function showDepartements() 
    {
        var myStyle = {
            'color': '#0000ff',
        };
        $.ajax({
            url: '/get_departements_map',
            type: 'GET',
            dataType: 'json',
            success: function (data) {
                if (data.features.length != 0) {
                    listeDepartement = L.geoJSON(data.features, {
                        style: myStyle
                    });
                    layerGroup = L.layerGroup([listeDepartement]).addTo(map);
                }
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }
    
    function removeMarker()
    {
        markers.clearLayers();
    }

    // Drupal.behaviors pour initialiser la carte
    Drupal.behaviors.cartoMapInit = {
        attach: function (context, settings) {
            // Utiliser once() pour s'assurer que la carte n'est initialisée qu'une seule fois
            once('carto-map-init', '#map', context).forEach(function (element) {
                // Initialiser les dimensions
                $('#map').css("height", (($(window).height() * 3 / 4) - mapmargin));
                $('#map').css("width", "100%");
                
                // Attacher l'événement resize
                $(window).on("resize", resize);
                resize();
                
                // Attendre que le DOM et les styles soient complètement chargés
                setTimeout(function() {
                    load_map();
                }, 50);
            });
        }
    };

})(jQuery, Drupal, once);
