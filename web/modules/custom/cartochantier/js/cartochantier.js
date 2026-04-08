jQuery(document).ready(function ($) {
	var dataExport = "";
    var map;
    var layerGroup;  
    var markers=new L.MarkerClusterGroup({
            maxClusterRadius: 50,
			iconCreateFunction: function (cluster) {
				var markersarray = cluster.getAllChildMarkers();				
				return L.divIcon({ html: cluster.getAllChildMarkers().length, className: 'mycluster', iconSize: L.point(40, 40) });
			}    
			
		});  
   
    var listeDepartement;
    var mapmargin = 0;
    var codePostalEvent = [];    
    $('#map').css("height", (($(window).height() * 3 / 4) - mapmargin));
    $('#map').css("width", "100%");
    $(window).on("resize", resize);
    resize();

    function resize() 
    {
        if ($(window).width() >= 980) {
            $('#map').css("height", (($(window).height() * 3 / 4) - mapmargin));
            $('#map').css("width", "100%");
        } else {
            $('#map').css("height", (($(window).height() * 3 / 4) - (mapmargin + 12)));
        }
    }

    function load_map() 
    {
        var cloudmadeUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
        var cloudmadeAttribution = 'Map data &copy; 2024 <a href="https://openstreetmap.org">OpenStreetMap</a> contributors';
		var cloudmade = new L.TileLayer(cloudmadeUrl, {minZoom: 2,maxZoom: 25, attribution: cloudmadeAttribution});
		var latlng = new L.LatLng(49.183333, -0.35);
		map = new L.Map('map', {center: latlng, zoom: 8, layers: [cloudmade], minZoom: 8, maxZoom: 15}); 
        map.addLayer(markers);
        resize();
        populate_type_Demande();
        populate_county_code();
        populate_postal_code();
        populate_towns();
        populate_epci();
        showChantier({});      
    }

    function showChantier(options) 
    {
        if (options.chantier) {
            if (options.departement) {
                $.ajax({
                    url: '/get_type_demande_par_departement/' + options.chantier + '/' + options.departement,
                    type: 'GET',
                    dataType: 'json',
                    success: function (json) {
                        $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                        });
                    },                  
                    error: function () {
                        console.log('Erreur serveur');
                    }
                });
            } else if (options.ville) {
                $.ajax({
                    url: '/get_type_demande_par_ville/' + options.chantier + '/' + options.ville,
                    type: 'GET',
                    dataType: 'json',
                    success: function (json) {
                        $.each(json, function (j, value) {
                            $.each(value, function (i, data) {
                                populateChantier(data);
                            });
                        });
                    },
                    error: function () {
                        console.log('Erreur serveur');
                    }
                });
            } else if (options.cp) {
                $.ajax({
                    url: '/get_type_demande_par_codepostal/' + options.chantier + '/' + options.cp,
                    type: 'GET',
                    dataType: 'json',
                    success: function (json) {
                        $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                        });
                    },
                    error: function () {
                        console.log('Erreur serveur');
                    }
                });
            } else if (options.epci) {
                $.ajax({
                    url: '/get_type_demande_par_epci/' + options.chantier + '/' + options.epci,
                    type: 'GET',
                    dataType: 'json',
                    success: function (json) {
                        $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                        });
                    },
                    error: function () {
                        console.log('Erreur serveur');
                    }
                });
            } else {
                $.ajax({
                    url: '/get_demande_par_type/' + options.chantier,
                    type: 'GET',
                    dataType: 'json',
                    success: function (json) { 
                        $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                        });
                    },
                    error: function () {
                        console.log('Erreur serveur');
                    }
                });
            }
        } else if (options.departement) {

            $.ajax({
                url: '/get_demande_par_departement/' + options.departement,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else if (options.cp) {
            $.ajax({
                url: '/get_demande_par_codePostal/' + options.cp,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                        });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        } else if (options.ville) {
            $.ajax({
                url: '/get_demande_par_ville/' + options.ville,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                               
                            populateChantier(data);                               
                        });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });

        } else if (options.epci) {
            $.ajax({
                url: '/get_demande_par_epci/' + options.epci,
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                        $.each(value, function (i, data) {                               
                             populateChantier(data);                               
                        });
                    });
                },               
                error: function () {
                    console.log('Erreur serveur ');
                }
            });
        } else {
            $.ajax({
                url: '/get_demande',
                type: 'GET',
                dataType: 'json',
                success: function (json) {
                    $.each(json, function (j, value) {
                            $.each(value, function (i, data) {                               
                                populateChantier(data);                               
                            });
                    });
                },
                error: function () {
                    console.log('Erreur serveur');
                }
            });
        }
    }

    function populateChantier(raw_data) 
    {   
    	setDataExport(raw_data);
    	
    	var geojsonMarkerOptions;
   
        switch (raw_data.CHEQUE) 
        {
            case "Audit énergétique" :
                if (raw_data.STATUT == 22) {
                    geojsonMarkerOptions = L.icon({
                    iconUrl: '/themes/custom/normandie/images/new_image/petale/audit_termine.png',
                     iconSize: [30,30]});                    
                } else {
                    geojsonMarkerOptions = L.icon({
                        iconUrl: '/themes/custom/normandie/images/new_image/petale/audit_en-cours.png',
                         iconSize: [30,30]});                    
                }
                break;
                
            case "Chèque travaux niveau I" : 
            case "Chèque travaux niveau II" :
            case "Chèque travaux niveau II option rénovateur" :
            case "Chèque travaux BBC" :
            case "Chèque travaux BBC Biosourcé" :
            case "Chèque travaux Sortie de passoire" :
            case "Chèque travaux Première étape BBC avec RGE" :
            case "Chèque travaux Première étape BBC avec Rénovateur" :
            case "Chèque travaux Rénovation globale BBC" :
                if (raw_data.STATUT == 22) {
                    geojsonMarkerOptions = L.icon({
                    iconUrl: '/themes/custom/normandie/images/new_image/petale/travaux_termines.png',
                     iconSize: [30,30]});                    
                } else {
                    geojsonMarkerOptions = L.icon({
                        iconUrl: '/themes/custom/normandie/images/new_image/petale/travaux_en-cours.png',
                         iconSize: [30,30]});                    
                }
                break;
                
            default :
                geojsonMarkerOptions = L.icon({
                    iconUrl: '/themes/custom/normandie/images/new_image/petale/petale_8dccc4.svg',
                    iconSize: [30,30]});
        }

        message = '<p>';
        if (raw_data.CHEQUE != null && raw_data.CHEQUE != '') {
            message += '<b>' + raw_data.CHEQUE + '</b>' ;
        }

        message += '<ul>';

        switch (raw_data.CHEQUE) {
            case "Audit énergétique" :
                if (raw_data.PROFESSIONNEL != null && raw_data.PROFESSIONNEL != ' ') {
                    message += '<li>Auditeur : ' + raw_data.PROFESSIONNEL + '</li>';
                }
                break;

            case "Chèque travaux niveau I" :
            case "Chèque travaux niveau II" :
            case "Chèque travaux niveau II option rénovateur" :
            case "Chèque travaux BBC" :
            case "Chèque travaux BBC Biosourcé" :
            case "Chèque travaux Sortie de passoire" :
            case "Chèque travaux Première étape BBC avec RGE" :
            case "Chèque travaux Première étape BBC avec Rénovateur" :
            case "Chèque travaux Rénovation globale BBC" :
                if (raw_data.PROFESSIONNEL != null && raw_data.PROFESSIONNEL != ' ') {
                    message += '<li>Rénovateur : ' + raw_data.PROFESSIONNEL + '</li>';
                }
                break;
        }

        if (raw_data.CONSEILLER != null && raw_data.CONSEILLER != '') {
            message += '<li>Conseiller : ' + raw_data.STRUCTURE + '</li>';
        }

        message += '</ul></p>';
                
        var marker = L.marker(L.latLng(raw_data.LAT,raw_data.LONG), {icon: geojsonMarkerOptions});
        marker.bindPopup(message);
        markers.addLayer(marker);                   
    }

    function populate_type_Demande() 
    {
        $.ajax({
            url: '/get_type_demande',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json.Nombre, function (i, DEMANDE) {
                    $('#chantier').append($('<option>').text(DEMANDE.type).attr('value', DEMANDE.valeur));
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function populate_county_code() 
    {
        $.ajax({
            url: '/get_liste_departement',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                if (json && json.structures) {
                    $.each(json.structures, function (i, county) {
                        $('#counties_codes').append($('<option>').text(county.DEPARTEMENT).attr('value', county.DEPARTEMENT));
                    });
                }
            },
            error: function (xhr, status, error) {
                console.log('Error loading departements:', status, error);
            }
        });
    }

    function populate_postal_code() 
    {
        $.ajax({
            url: '/get_liste_codePostal',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                if (json && json.structures) {
                    $.each(json.structures, function (i, postal_code) {
                        $('#postal_codes').append($('<option>').text(postal_code.CODE_POSTAL).attr('value', postal_code.CODE_POSTAL));
                        codePostalEvent.push(postal_code.CODE_POSTAL);
                    });
                }
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function populate_towns() 
    {
        $.ajax({
            url: '/get_liste_ville',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                if (json && json.structures) {
                    $.each(json.structures, function (i, town) {
                        $('#towns').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
                    });
                }
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function populate_epci() 
    {
        $.ajax({
            url: '/get_liste_epci',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                if (json && json.epci) {
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

    $(function () {
        $("#counties_codes").change(function () {
            dataExport = "";
            if ($("#counties_codes").val() != '') {
                if ($("#chantier").val() != null) {
                    $('#postal_codes').find('option:first').prop('selected','selected');
                    $('#towns').find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                    filtreCpByDep();
                    filtreVilleByDep();
                } else {
                    $('#postal_codes').find('option:first').prop('selected','selected');
                    $('#towns').find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                    filtreCpByDep();
                    filtreVilleByDep();
                }
            }
        });
    });

    $(function () {
        $("#postal_codes").change(function () {
            dataExport = "";
            if ($("#postal_codes").val() != '') {
                if ($("#chantier").val() != null) {
                    $('#counties_codes').find('option:first').prop('selected','selected');
                    $("#towns").find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                    filtreVilleByPostalCode();
                } else {
                    $('#counties_codes').find('option:first').prop('selected','selected');
                    $("#towns").find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                    filtreVilleByPostalCode();
                }

            }
        });
    });

    $(function () {
        $("#towns").change(function () {
            dataExport = "";
            if ($("#towns").val() != '') {
                if ($("#chantier").val() != '') {
                    $("#counties_codes").find('option:first').prop('selected','selected');
                    $("#postal_codes").find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                } else {
                    $("#counties_codes").find('option:first').prop('selected','selected');
                    $("#postal_codes").find('option:first').prop('selected','selected');
                    $("#epci").find('option:first').prop('selected','selected');
                }
            }
        });
    });

    $(function () {
        $("#epci").change(function () {
            dataExport = "";
            if ($("#epci").val() != '') {
                if ($("#chantier").val() != '') {
                    $("#counties_codes").find('option:first').prop('selected','selected');
                    $("#postal_codes").find('option:first').prop('selected','selected');
                    $("#towns").find('option:first').prop('selected','selected');
                } else {
                    $("#counties_codes").find('option:first').prop('selected','selected');
                    $('#postal_codes').find('option:first').prop('selected','selected');
                    $("#towns").find('option:first').prop('selected','selected');
                }
            }
        });
    });

    $(function () {
        $("#reset").click(function () {
            removeMarker();
            dataExport = "";
            $('#postal_codes').find('option:first').prop('selected','selected');
            $("#counties_codes").find('option:first').prop('selected','selected');
            $('#towns').find('option:first').prop('selected','selected');
            $("#chantier").find('option:first').prop('selected','selected');
            $("#epci").find('option:first').prop('selected','selected');
            reinitialiserCp();
            reinitialiserTowns();
            showChantier({});
        });
    });

    $(function () {
        $("#search").click(function (ev) {
            ev.preventDefault();
            removeMarker();
            dataExport = "";
            showChantier({epci: $("#epci").val(), chantier: $("#chantier").val(), ville: $("#towns").val(), cp: $("#postal_codes").val(), departement: $("#counties_codes").val()});
        });
    });

    function filtreCpByDep() 
    {
        var valcpshort = $("#counties_codes").val();
        var taille = codePostalEvent.length; 
        var cp = document.getElementById('postal_codes');       
        cp.length = 0;
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {
            if (valcpshort === codePostalEvent[i].substr(0, 2))
                $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }

    function reinitialiserCp()
    {
        var taille = codePostalEvent.length;
        var cp = document.getElementById('postal_codes');
        cp.length = 0;
        $('#postal_codes').append($('<option>').text("Code postal").attr('value', ""));
        for (var i = 0; i < taille; i++) {
            $('#postal_codes').append($('<option>').text(codePostalEvent[i]).attr('value', codePostalEvent[i]));
        }
    }

    function reinitialiserTowns() 
    {
        var towns = document.getElementById('towns');
        towns.length = 0;
        $('#towns').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_ville',
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#towns').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function filtreVilleByPostalCode() 
    {
        var codepostale = $('#postal_codes').val();
        var town = document.getElementById('towns');
        town.length = 0;
        $('#towns').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_ville_par_codePostal/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#towns').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
    }

    function filtreVilleByDep() 
    {
        var codepostale = $('#counties_codes').val();
        var town = document.getElementById('towns');
        town.length = 0;
        $('#towns').append($('<option>').text("Ville").attr('value', ""));
        $.ajax({
            url: '/get_liste_ville_par_departement/' + codepostale,
            type: 'GET',
            dataType: 'json',
            success: function (json) {
                $.each(json, function (j, value) {
                    $.each(value, function (i, town) {
                        $('#towns').append($('<option>').text(town.VILLE).attr('value', town.INSEE));
                    });
                });
            },
            error: function () {
                console.log('Erreur serveur');
            }
        });
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
    
    function setDataExport(data)
    {
        dataExport += "\n";
        dataExport += data.CHEQUE + ";" + 
                      data.PROFESSIONNEL + ";" +
                      data.CONSEILLER + ";" + 
                      data.STRUCTURE + ";" +
                      data.CODE_POSTAL + ";" +
                      data.VILLE + ";" + 
                      data.STATUT_LABEL;        
    }

    downloadCSV = () => {
        dataExport = "Type de chèque;Entreprise ou Auditeur;Conseiller;Structure-Conseil;Code postal;Ville ;Statut" + dataExport;
        
        const hiddenElement = document.createElement('a');
        hiddenElement.href = 'data:text/csv;charset=utf-8,%EF%BB%BF'+encodeURIComponent(dataExport);
        hiddenElement.target = '_blank';
        hiddenElement.download = 'export_logement_filtre.csv';
        document.body.appendChild(hiddenElement);
        hiddenElement.click();
        hiddenElement.remove();
    };

    $('#download').on('click', downloadCSV);

    load_map();
});

