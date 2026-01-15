document.addEventListener("DOMContentLoaded", function () {

    // Set global DataTables defaults for the number of rows to show
    if (typeof $.fn.dataTable !== 'undefined') {
        $.extend(true, $.fn.dataTable.defaults, {
            "lengthMenu": [[10, 25, 50, 100, 1000, -1], [10, 25, 50, 100, 1000, "All"]]
        });
    }
    
    if (error) {
        alert(error)
        showAlert(error);
    }

    $('#loginForm').on('show.bs.collapse', function () {
        let alertBox = document.getElementById('alertBox');
        if (!alertBox) {
            return;
        }
        alertBox.classList.remove('show');
    });

    document.addEventListener('submit', (event) => {
        if (event.target.matches('.js-async-form')) {
            event.preventDefault();
            postRequest(event.target.action, new FormData($("#settingForm")[0]), true);
        }
    });

    $('body').on('click', ".js-open-modal", function (e) {
        let data = [];
        if (this.dataset.id) {
            data = {'id': this.dataset.id};
        }
        requestModal(this.href, data);
        e.preventDefault();
    });

    $("[data-hide]").on("click", function () {
        $(this).closest("." + $(this).attr("data-hide")).hide();
    });

    $(".log").click(function () {
        $("#alertBox").removeClass('show');
    });

    $(".user").click(function () {
        $("#alertBox").removeClass('show');
    });

    toggleLoading();

    $.fn.toggleDisabled = function () {
        return this.each(function () {
            this.disabled = !this.disabled;
        });
    };

    $(document).on('keydown.autocomplete', '.js-species-autocomplete', function () {
        var id = '';
        if (typeof ($(this).attr('id').split('_')[1]) != 'undefined') {
            id = $(this).attr('id').split('_')[1]
        }
        $(this).autocomplete({
            source: function (request, response) {
                $.post(baseUrl + '/species/getList', {term: request.term})
                    .done(function (data) {
                        response(JSON.parse(data));
                    })
                    .fail(function (response) {
                        showAlert(JSON.parse(response.responseText).message);
                        response(null);
                    });
            },
            minLength: 3,
            change: function (event, ui) {
                if (!ui.item) {
                    $(this).val('');
                    let type = $(this).data('type');
                    if (id != '') {
                        $('.js-species-id' + id + '[data-type=' + type + ']').val($("#old_id" + id).val());
                        $(this).val($("#old_name" + id).val());
                        $("#animal_sound_type" + id).attr('disabled', false)
                    } else {
                        $('.js-species-id' + id + '[data-type=' + type + ']').val('');
                        $("#animal_sound_type").empty()
                        $("#animal_sound_type" + id).attr('disabled', true)
                    }
                    //$('#reviewSpeciesId').val('');
                }
            },
            select: function (e, ui) {
                $(this).val(ui.item.label.split('(')[0]);
                let type = $(this).data('type');
                $('.js-species-id' + id + '[data-type=' + type + ']').val(ui.item.value);
                $.post(baseUrl + '/species/getSoundType', {
                    taxon_class: ui.item.class,
                    taxon_order: ui.item.taxon_order
                })
                    .done(function (data) {
                        console.log(ui.item)
                        $('#order' + id).text(ui.item.taxon_order)
                        $('#family' + id).text(ui.item.family)
                        $('#genus' + id).text(ui.item.genus)
                        if (data == '') {
                            $("#animal_sound_type" + id).empty()
                            $("#animal_sound_type" + id).attr('disabled', true)
                        } else {
                            $("#animal_sound_type" + id).attr('disabled', false)
                            $("#taxon_class" + id).val(ui.item.class)
                            $("#taxon_order" + id).val(ui.item.taxon_order)
                            var json = JSON.parse(data)
                            $("#animal_sound_type" + id).empty()
                            $("#animal_sound_type" + id).append('<option value="0"></option>');
                            for (var key in json) {
                                $("#animal_sound_type" + id).append("<option value=" + json[key]['sound_type_id'] + ">" + json[key]['name'] + "</option>");
                            }
                        }
                    })
                    .fail(function () {
                        showAlert("Type loading failure.");
                    });
                e.preventDefault();
            }
        });
    });

    $(document).on('keydown.autocomplete', '.js-search-autocomplete', function () {
        $(this).autocomplete({
            source: function (request, response) {
                $.post(baseUrl + '/project/search', {term: request.term})
                    .done(function (data) {
                        response(JSON.parse(data));
                    })
                    .fail(function (response) {
                        showAlert(JSON.parse(response.responseText).message);
                        response(null);
                    });
            },
            minLength: 2,
            select: function (e, ui) {
                window.open(baseUrl + '/collection/' + ui.item.url + '/' + ui.item.value, '_blank');
                e.preventDefault();
            }
        });
    });
});

function showAlert(message) {
    //TODO: Move hide login form to another place
    $('#loginForm').collapse('hide');

    let alertDiv = document.getElementById('alertBox');

    if (alertDiv) {
        alertDiv.getElementsByTagName('p')[0].textContent = message;
        return;
    }

    alertDiv = document.createElement('div');
    alertDiv.id = 'alertBox';
    alertDiv.classList.add('alert', 'alert-dismissible', 'alert-secondary', 'fade', 'show');
    alertDiv.setAttribute('role', 'alert');

    let paragraph = document.createElement('p');
    paragraph.textContent = message;

    let button = document.createElement('button');
    button.type = 'button';
    button.className = 'close';
    button.setAttribute('data-dismiss', 'alert');
    button.setAttribute('aria-label', 'close');

    let span = document.createElement('span');
    span.innerHTML = '&times;';
    button.appendChild(span);

    alertDiv.appendChild(paragraph);
    alertDiv.appendChild(button);

    let header = document.getElementsByTagName('header')[0];
    document.body.insertBefore(alertDiv, header);
}

function toggleLoading() {
    $('.loading').toggle();
}

function requestModal(href, data = [], showLoading = false, backdrop = true) {
    postRequest(href, data, false, showLoading, function (response) {
        $('#modalWindows').html(response.data);
        if (backdrop) {
            $("#modal-div").modal('show');
        } else {
            $('#modal-div').modal({backdrop: false});
        }

    });
}

function postRequest(href, data = [], showMessage = false, showLoading = false, callback) {
    asyncRequest('POST', href, data, showMessage, showLoading, callback);
}

function deleteRequest(href, data = [], showMessage = false, showLoading = false, callback) {
    asyncRequest('DELETE', href, data, showMessage, showLoading, callback);
}

function asyncRequest(type, href, data = [], showMessage = false, showLoading = false, callback) {
    if (showLoading) {
        toggleLoading();
    }
    data = jsToFormData(data)
    $.ajax({
        type: type,
        url: href,
        data: data,
        dataType: 'json',
        processData: false,
        contentType: false,
    })
        .done(function (response) {
            if (showMessage) {
                showAlert(response.message);
            }
            if (callback && typeof callback === 'function') {
                callback(response);
            }
        })
        .fail(function (response) {
            if (response.status != 0) {
                if (response.status === 401) {
                    //showAlert('You are not authenticated. Please, log in.');
                    //window.location.href = baseUrl;
                }
                if (!response.responseJSON) {
                    showAlert('Error ' + response.message);
                    return;
                }
                showAlert(response.responseJSON.message);
            }
        })
        .always(function () {
            if (showLoading) {
                toggleLoading();
            }
        });
}

function getCookie(cname) {
    let name = cname + "=";
    let ca = document.cookie.split(';');

    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) === ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return '';
}

/*
 * Function for saving the fields of a list with forms.
 */
function saveFormList(element, url, callback) {
    let row = element.closest("tr");
    let columns = row.find("input, select, textarea");
    let formData = new FormData()
    let value = '';
    columns.each(function (i, item) {
        value = item.value;
        if (item.className == 'js-checkbox') {
            return
        }
        if (item.type === "checkbox" && item.checked) {
            value = 1;
        } else if (item.type === "checkbox" && !item.checked) {
            value = 0;
        }
        if (typeof ($(this).parent().attr("data-search")) != "undefined") {
            if ($(this).is('select')) {
                if ($(this).parent().attr("data-search") != $(this).find("option:selected").text()) {
                    $(this).parent().attr('data-search', $(this).find("option:selected").text());
                    $(this).parent().attr('data-order', $(this).find("option:selected").text());
                }
            } else {
                if ($(this).parent().attr("data-search") != value) {
                    $(this).parent().attr('data-search', value);
                    $(this).parent().attr('data-order', value);
                }
            }
        }
        if (item.type == 'file') {
            formData.append(item.name, $("#" + item.id)[0].files[0]);
        } else if (item.type == 'date' || item.type == 'time')
            if (item.value == "") {
                return
            } else {
                formData.append(item.name + "_" + item.type, value);
            }
        else {
            formData.append(item.name + "_" + item.type, value);
        }
    });
    postRequest(baseUrl + '/' + url, formData, false, false, callback);
}

function jsToFormData(config) {
    if (config.constructor.name != "FormData") {
        const formData = new FormData();
        Object.keys(config).forEach((key) => {
            formData.append(key, config[key]);
        })
        return formData;
    } else {
        return config;
    }
}

$(document).keydown(function (event) {
    if (event.keyCode == 27) {
        $('.modal-backdrop').remove();
        $('#modal-div').hide()

    }
});
$('.js-all-checkbox').change(function () {
    var isChecked = $(this).prop('checked');
    $('.js-checkbox').prop('checked', isChecked).trigger('change');
    checkboxChange()
});

$('table').on('draw.dt', function () {
    let allChecked = checkboxChange()
    if (allChecked) {
        $('.js-all-checkbox').prop('checked', true);
    } else {
        $('.js-all-checkbox').prop('checked', false);
    }
});

$('table').on('change', '.js-checkbox', function () {
    let allChecked = checkboxChange()
    if (allChecked) {
        $('.js-all-checkbox').prop('checked', true);
    } else {
        $('.js-all-checkbox').prop('checked', false);
    }
});

function checkboxChange() {
    let selectedCount = 0;
    let allChecked = true;

    $('.js-checkbox').each(function () {
        if (!$(this).prop('checked')) {
            allChecked = false;
        } else {
            selectedCount++;
        }
    });

    if (selectedCount > 0) {
        $('button[name="table-btn"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary');
        $('button[name="table-btn"]').prop('disabled', false);
    } else {
        $('button[name="table-btn"]').removeClass('btn-outline-primary').addClass('btn-outline-secondary');
        $('button[name="table-btn"]').prop('disabled', true);
    }
    if ($('button[name="table-btn"][data-target="#editPassword"]').length > 0) {
        if (selectedCount === 1) {
            $('button[name="table-btn"][data-target="#editPassword"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            $('button[name="table-btn"][data-target="#editPassword"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#editPassword"]').removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#editPassword"]').prop('disabled', true);
        }
    }
    if ($('button[name="table-btn"][data-target="#editDescription"]').length > 0) {
        if (selectedCount === 1) {
            $('button[name="table-btn"][data-target="#editDescription"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            $('button[name="table-btn"][data-target="#editDescription"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#editDescription"]').removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#editDescription"]').prop('disabled', true);
        }
    }
    if ($('button[name="table-btn"][data-target="#download"]').length > 0) {
        if (selectedCount === 1) {
            $('button[name="table-btn"][data-target="#download"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            $('button[name="table-btn"][data-target="#download"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#download"]').removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#download"]').prop('disabled', true);
        }
    }
    if ($('button[name="table-btn"][data-target="#task"]').length > 0) {
        if (selectedCount === 1) {
            $('button[name="table-btn"][data-target="#task"]').removeClass('btn-outline-secondary').addClass('btn-outline-primary');
            $('button[name="table-btn"][data-target="#task"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#task"]').removeClass('btn-outline-primary').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#task"]').prop('disabled', true);
        }
    }
    if ($('button[name="table-btn"][data-target="#delete"]').length > 0) {
        if (selectedCount === 1) {
            $('button[name="table-btn"][data-target="#delete"]').removeClass('btn-outline-secondary').removeClass('btn-outline-primary').addClass('btn-outline-danger');
            $('button[name="table-btn"][data-target="#delete"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#delete"]').removeClass('btn-outline-primary').removeClass('btn-outline-danger').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#delete"]').prop('disabled', true);
        }
    }
    if ($('button[name="table-btn"][data-target="#deletion"]').length > 0) {
        if (selectedCount > 0) {
            $('button[name="table-btn"][data-target="#deletion"]').removeClass('btn-outline-secondary').removeClass('btn-outline-primary').addClass('btn-outline-danger');
            $('button[name="table-btn"][data-target="#deletion"]').prop('disabled', false);
        } else {
            $('button[name="table-btn"][data-target="#deletion"]').removeClass('btn-outline-primary').removeClass('btn-outline-danger').addClass('btn-outline-secondary');
            $('button[name="table-btn"][data-target="#deletion"]').prop('disabled', true);
        }
    }
    return allChecked
}

$('#btn-search').click(function () {
    $('#form-search').fadeToggle()
    $('.js-search-autocomplete').focus()
})
$(document).ready(function () {
    if ($(window).width() < 768) {
        $('#form-search').fadeToggle()
    }
});

/**
 * Reset pagination position for a DataTable to the first page.
 * @param {DataTable} table The DataTable instance to reset pagination for
 */
function resetDataTablesPagination(table) {
    if (!table) return;
    
    try {
        // Clear the state and reset to first page
        table.state.clear();
        table.page(0).draw();
    } catch (e) {
        console.error('Error resetting DataTable pagination:', e);
    }
}

// Reset pagination to first page when switching projects/collections
$(document).on('submit', '#projectForm, #collectionForm', function () {
    if (typeof $.fn.dataTable === 'undefined') {
        return;
    }
    
    // Reset pagination for each visible DataTable
    $('table.dataTable').each(function() {
        var table = $(this).DataTable();
        resetDataTablesPagination(table);
    });
});
