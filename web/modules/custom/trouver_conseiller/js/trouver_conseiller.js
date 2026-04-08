(function ($, Drupal, once) {
    'use strict';
    
    Drupal.behaviors.trouver_conseiller = {
        attach: function (context, settings) {

            function populate_towns(value, onload) {
                var v_test;
                try {
                    v_test = parseInt(value, 10);
                }
                catch(e) {

                }  
                if(jQuery.isNumeric(v_test)) {
                    jQuery.ajax({
                        url: '/trouver-conseiller/ville?value='+value,
                        type: 'GET',
                        dataType: 'json',
                        success: function (json) {
                            jQuery('.trouver-conseiller-form-ville-select option').remove();
    jQuery('.trouver-conseiller-form-ville-select').append(jQuery('<option>').text("Choisissez votre ville").attr('value', 0));
                            jQuery.each(json, function (j, value) {
                                jQuery.each(value, function (i, town) {
                                    jQuery('.trouver-conseiller-form-ville-select').append(jQuery('<option>').text(town.VILLE).attr('value', town.CP));
                                });
                            });
                            if(onload === 1) {
                                var valCity = jQuery('#tc-js-city').val();
                                jQuery('.trouver-conseiller-form-ville-select option[value='+valCity+']').attr('selected','selected');
                            }
                        },
                        error: function () {
                            console.log('Erreur serveur');
                        }
                    });
                }
                else {

                }

            }

            function calculate_revenu(value, onload) {
                try {
                    value = parseInt(value);
                }
                catch(e) {

                }
                if(jQuery.isNumeric(value)) {
                    jQuery.ajax({
                        url: '/trouver-conseiller/critere?value='+value,
                        type:'GET',
                        dataType: 'json',
                        success: function (json) {
                            jQuery('.trouver-conseiller-form-revenu-select option').remove();
                            jQuery('.trouver-conseiller-form-revenu-val').attr('value', json);
                            jQuery('.trouver-conseiller-form-revenu-select').append(jQuery('<option>').text("Choisissez votre revenu fiscal").attr('value', 0));
                            jQuery('.trouver-conseiller-form-revenu-select').append(jQuery('<option>').text("Revenu fiscal inférieur à "+json).attr('value', 'inf'));
                            jQuery('.trouver-conseiller-form-revenu-select').append(jQuery('<option>').text("Revenu fiscal supérieur à "+json).attr('value', 'sup'));
                            if(onload === 1) {
                                var valState = jQuery('#tc-js-state').val();
                                jQuery('.trouver-conseiller-form-revenu-select option[value='+valState+']').attr('selected','selected');
                            }
                        },
                        error: function () {
                            console.log('Erreur serveur');
                        }
                    })
                }
                else {

                }

            }

            function throttle(f, delay){
                var timer = null;
                return function(){
                    var context = this, args = arguments;
                    clearTimeout(timer);
                    timer = window.setTimeout(function(){
                            f.apply(context, args);
                        },
                        delay || 500);
                };
            }

            once('trouver-conseiller-init', '.trouver-conseiller-form-nb-personne', context).forEach(function(element) {
                var $element = $(element);
                
                $('.trouver-conseiller-form-revenu-select').attr('disabled', 'disabled');
                $('.trouver-conseiller-form-ville-select').attr('disabled', 'disabled');
                
                if($element.attr('value') !== '') {
                    if($.isNumeric($element.attr('value'))) {
                        $('.trouver-conseiller-form-revenu-select').removeAttr('disabled');
                        calculate_revenu($element.attr('value'), 1);
                    }
                }
                
                var $codePostal = $('.trouver-conseiller-form-code-postal');
                if($codePostal.attr('value') !== '') {
                    if($.isNumeric($codePostal.attr('value'))) {
                        $('.trouver-conseiller-form-ville-select').removeAttr('disabled');
                        populate_towns($codePostal.attr('value'), 1);
                    }
                }
            });

            once('trouver-conseiller-cp', '.trouver-conseiller-form-code-postal', context).forEach(function(element) {
                $(element).on('blur', throttle(function() {
                    if(this.value !== '') {
                        if($.isNumeric(this.value)) {
                            $('.trouver-conseiller-form-ville-select').removeAttr('disabled');
                            populate_towns(this.value, 0);
                        }
                        else {
                            $('.trouver-conseiller-form-ville-select option').remove();
                            $('.trouver-conseiller-form-ville-select').attr('style', 'color:#EC6607').append($('<option>').text('Entrez un CP valide').attr('value', 0));
                        }
                    }
                }));
            });

            once('trouver-conseiller-nb', '.trouver-conseiller-form-nb-personne', context).forEach(function(element) {
                $(element).on('blur', throttle(function() {
                    if(this.value !== '') {
                        if($.isNumeric(this.value)) {
                            $('.trouver-conseiller-form-revenu-select').removeAttr('disabled');
                            calculate_revenu(this.value, 0);
                        }
                        else {
                            $('.trouver-conseiller-form-revenu-select option').remove();
                            $('.trouver-conseiller-form-revenu-select').append($('<option>').text('Renseignez le nombre de personnes').attr('value', 0));
                        }
                    }
                }));
            });

            once('trouver-conseiller-search', '#search', context).forEach(function(element) {
                $(element).on('click', function(ev) {
                    var error = false;
                    ev.preventDefault();

                    if(jQuery('.trouver-conseiller-form-nb-personne').val() == ''){
                        error = true;
                        jQuery('.trouver-conseiller-form-nb-personne').attr('style', 'border-color:#EC6607');
                    } else {
                        jQuery('.trouver-conseiller-form-nb-personne').removeAttr('style');
                    }

                    if(jQuery('.trouver-conseiller-form-revenu-select').val() == 0){
                        error = true;
                        jQuery('.trouver-conseiller-form-revenu-select').attr('style', 'color:#EC6607');
                    } else {
                        jQuery('.trouver-conseiller-form-revenu-select').removeAttr('style');
                    }

                    if(jQuery('.trouver-conseiller-form-code-postal').val() == 0){
                        error = true;
                        jQuery('.trouver-conseiller-form-code-postal').attr('style', 'border-color:#EC6607');
                    } else {
                        jQuery('.trouver-conseiller-form-code-postal').removeAttr('style');
                    }

                    if(jQuery('.trouver-conseiller-form-ville-select').val() == 0){
                        error = true;
                        jQuery('.trouver-conseiller-form-ville-select').attr('style', 'color:#EC6607');
                    } else {
                        jQuery('.trouver-conseiller-form-ville-select').removeAttr('style');
                    }

                    if(error == false) {

                        jQuery.ajax({
                            url: '/trouver-conseiller/calcul',
                            type: 'POST',
                            data:{
                                nb_personne: jQuery('.trouver-conseiller-form-nb-personne').val(),
                                ville: jQuery('.trouver-conseiller-form-ville-select').val(),
                                revenu: jQuery('.trouver-conseiller-form-revenu-select').val(),
                            },
                            dataType: 'json',
                            success: function (json) {

                                if (json.success == 1) {

                                    var results = json.results;
                                    var table = '<table>' +
                                        '<tr>' +
                                        '<td>Nom</td>' +
                                        '<td>Adresse</td>' +
                                        '<td>Code postal</td>' +
                                        '<td>Ville</td>' +
                                        '<td>Téléphone</td>' +
                                        '<td>Site internet</td>' +
                                        '</tr>';

                                    for (var key in results) {
                                        if (results.hasOwnProperty(key)) {
                                            table += '<tr>' +
                                                '<td>' + results[key].nom + '</td>' +
                                                '<td>' + results[key].adresse1 + '</td>' +
                                                '<td>' + results[key].code_postal + '</td>' +
                                                '<td>' + results[key].ville + '</td>' +
                                                '<td>' + results[key].telephone + '</td>' +
                                                '<td>' + results[key].site_internet + '</td>' +
                                                '</tr>';
                                        }
                                    }

                                    table += '</table>';

                                    jQuery('#map').html(table);

                                } else {

                                    var errorMsg = json.error;
                                    var table = '<table>' +
                                        '<tr>' +
                                        '<td>Nom</td>' +
                                        '<td>Adresse</td>' +
                                        '<td>Code postal</td>' +
                                        '<td>Ville</td>' +
                                        '<td>Téléphone</td>' +
                                        '<td>Site internet</td>' +
                                        '</tr>' +
                                        '<tr>' +
                                        '<td colspan="6">' + errorMsg + '</td>' +
                                        '</tr>' +
                                        '</table>';

                                    jQuery('#map').html(table);
                                }
                            },
                            error: function () {
                                console.log('Erreur serveur  000');
                            }
                        });
                    }

                });
            });

        }
    };
}(jQuery, Drupal, once));
