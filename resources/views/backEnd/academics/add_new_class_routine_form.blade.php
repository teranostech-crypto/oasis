<style>
    .soft-input-group { margin-bottom: 16px; }
    .soft-input-group label {
        font-size: 11px; text-transform: uppercase; color: #64748b; 
        font-weight: 700; display:block; margin-bottom: 6px; letter-spacing: 0.5px;
    }
    .soft-input {
        border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; 
        font-size: 13px; color: #334155; width: 100%; transition: all 0.2s ease;
        background: #f8fafc; height: 42px;
    }
    .soft-input:focus {
        border-color: #E56A63; background: #fff; outline: none; box-shadow: 0 0 0 3px rgba(229, 106, 99, 0.1);
    }
    .modal-btn-save {
        background: #E56A63; border: none; color: white; padding: 10px 24px; 
        border-radius: 8px; font-weight: 600; font-size: 13px; transition: 0.3s;
    }
    .modal-btn-save:hover { background: #d35952; box-shadow: 0 4px 12px rgba(229, 106, 99, 0.2); }
    .modal-btn-cancel {
        background: #f1f5f9; border: none; color: #64748b; padding: 10px 24px; 
        border-radius: 8px; font-weight: 600; font-size: 13px; transition: 0.3s;
    }
    .modal-btn-cancel:hover { background: #e2e8f0; color: #334155; }
    .bootstrap-datetimepicker-widget, .xdsoft_datetimepicker, .datepicker {
        z-index: 999999 !important;
    }
</style>

{{ Form::open(['class' => 'form-horizontal', 'id' => 'dynamicRoutineForm', 'route' => 'add-new-class-routine-store', 'method' => 'POST']) }}
    
    @if(isset($routine))
        <input type="hidden" name="routine_id" value="{{ $routine->id }}">
    @endif
    
    <div class="row">
        <div class="col-lg-4 soft-input-group">
            <label>Academic Year *</label>
            <select class="soft-input" name="academic_year" id="modal_academic_year" required>
                <option value="">Select Year</option>
                @foreach($academic_years as $year)
                    <option value="{{ $year->id }}" {{ (isset($routine) && $routine->academic_id == $year->id) || (!isset($routine) && getAcademicId() == $year->id) ? 'selected' : '' }}>
                        {{ $year->year }} [{{ $year->title }}]
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-4 soft-input-group">
            <label>Class *</label>
            <select class="soft-input" name="class_id" id="modal_class_id" required>
                <option value="">Select Class</option>
            </select>
        </div>
        <div class="col-lg-4 soft-input-group">
            <label>Section *</label>
            <select class="soft-input" name="section_id" id="modal_section_id" required>
                <option value="">Select Section</option>
            </select>
        </div>

        <div class="col-lg-12"><hr style="border-top: 1px dashed #e2e8f0; margin: 10px 0 20px 0;"></div>

        <div class="col-lg-4 soft-input-group">
            <label>Day *</label>
            <select class="soft-input" name="day" id="modal_day" required>
                <option value="">Select Day</option>
                @foreach($days as $day)
                    <option value="{{ $day->id }}" {{ isset($routine) && $routine->day == $day->id ? 'selected' : '' }}>{{ $day->name }}</option>
                @endforeach
            </select>
        </div>
        
        <div class="col-lg-4 soft-input-group">
            <label>Start Time *</label>
            <div class="row no-gutters input-right-icon" style="width: 100%;">
                <div class="col">
                    <div class="primary_input">
                        <input class="soft-input time start_time_required form-control" type="text" name="start_time" value="{{ isset($routine) && $routine->start_time ? date('H:i', strtotime($routine->start_time)) : '07:20' }}" required id="start_time">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" style="background: transparent; border: none; padding-right: 14px; color: #94a3b8; outline: none;">
                        <i class="ti-timer"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 soft-input-group">
            <label>End Time *</label>
            <div class="row no-gutters input-right-icon" style="width: 100%;">
                <div class="col">
                    <div class="primary_input">
                        <input class="soft-input time end_time_required form-control" type="text" name="end_time" value="{{ isset($routine) && $routine->end_time ? date('H:i', strtotime($routine->end_time)) : '08:00' }}" required id="end_time">
                    </div>
                </div>
                <div class="col-auto">
                    <button type="button" style="background: transparent; border: none; padding-right: 14px; color: #94a3b8; outline: none;">
                        <i class="ti-timer"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="col-lg-12 soft-input-group" style="background: #fff7ed; padding: 12px; border-radius: 8px; border: 1px solid #ffedd5;">
            <div class="d-flex align-items-center">
                <input type="checkbox" id="modal_is_break" name="is_break" value="1" style="width: 18px; height: 18px; margin-right: 10px; accent-color: #E56A63;" {{ isset($routine) && $routine->is_break == 1 ? 'checked' : '' }}>
                <label for="modal_is_break" style="margin: 0; color: #d97706; font-size: 13px;">Mark this schedule as Break Time (Rest / Lunch)</label>
            </div>
        </div>

        <div class="col-lg-12" id="academic_meta_section">
            <div class="row">
                <div class="col-lg-4 soft-input-group">
                    <label>Subject *</label>
                    <select class="soft-input" name="subject_id" id="modal_subject_id">
                        <option value="">Select Subject</option>
                    </select>
                </div>
                <div class="col-lg-4 soft-input-group">
                    <label>Teacher / Instructor *</label>
                    <select class="soft-input" name="teacher_id" id="modal_teacher_id">
                        <option value="">Select Teacher</option>
                    </select>
                </div>
                <div class="col-lg-4 soft-input-group">
                    <label>Classroom *</label>
                    <select class="soft-input" name="room_id" id="modal_room_id">
                        <option value="">Select Room</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ isset($routine) && $routine->room_id == $room->id ? 'selected' : '' }}>{{ $room->room_no }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mt-3" style="gap: 10px;">
        <button type="button" class="modal-btn-cancel" data-dismiss="modal">Cancel</button>
        <button type="submit" class="modal-btn-save">Save Routine</button>
    </div>
{{ Form::close() }}

<script>
$(document).ready(function() {
    var url = $("#url").val();

    // --- RE-INITIALIZE TIMEPICKER UNTUK MODAL AJAX ---
    setTimeout(function() {
        if ($.fn.datetimepicker) {
            $('.time').datetimepicker({
                format: 'HH:mm'
            });
        }
        
        // Agar saat icon jam (ti-timer) diklik, popup jamnya muncul
        $('.ti-timer').closest('button').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.input-right-icon').find('.time').focus();
        });
    }, 400);
    // -------------------------------------------------
    
    // Inject Data Edit (Dari PHP jika ada ID yg diklik)
    var edit_academic_id = "{{ isset($routine) ? $routine->academic_id : getAcademicId() }}";
    var edit_class_id    = "{{ isset($routine) ? $routine->class_id : '' }}";
    var edit_section_id  = "{{ isset($routine) ? $routine->section_id : '' }}";
    var edit_subject_id  = "{{ isset($routine) ? $routine->subject_id : '' }}";
    var edit_teacher_id  = "{{ isset($routine) ? $routine->teacher_id : '' }}";

    // 1. Break Time Toggle
    function toggleBreak() {
        if ($('#modal_is_break').is(':checked')) {
            $('#academic_meta_section').slideUp('fast');
            $('#modal_subject_id, #modal_teacher_id, #modal_room_id').removeAttr('required');
        } else {
            $('#academic_meta_section').slideDown('fast');
            $('#modal_subject_id, #modal_teacher_id, #modal_room_id').prop('required', true);
        }
    }
    $('#modal_is_break').on('change', toggleBreak);
    toggleBreak();

    // 2. FETCH CLASSES VIA CUSTOM ENDPOINT
    function loadClasses(academic_year, selected_class = null) {
        if(!academic_year) return;
        $('#modal_class_id').html('<option value="">Loading...</option>');
        $.get(url + '/routine-modal-get-class?academic_year=' + academic_year, function(data) {
            var options = '<option value="">Select Class</option>';
            $.each(data, function(i, item) {
                let isSelected = (selected_class == item.id) ? 'selected' : '';
                options += `<option value="${item.id}" ${isSelected}>${item.class_name}</option>`;
            });
            $('#modal_class_id').html(options);
            
            // Autoload section jika Edit
            if(selected_class && edit_section_id) {
                loadSections(selected_class, edit_section_id);
            }
        });
    }

    // 3. FETCH SECTIONS VIA CUSTOM ENDPOINT
    function loadSections(class_id, selected_section = null) {
        if(!class_id) return;
        $('#modal_section_id').html('<option value="">Loading...</option>');
        $.get(url + '/routine-modal-get-section?class_id=' + class_id, function(data) {
            var options = '<option value="">Select Section</option>';
            $.each(data, function(i, item) {
                let isSelected = (selected_section == item.id) ? 'selected' : '';
                options += `<option value="${item.id}" ${isSelected}>${item.section_name}</option>`;
            });
            $('#modal_section_id').html(options);
            
            // Autoload subject jika Edit
            if(selected_section && edit_subject_id) {
                loadSubjects(class_id, selected_section, edit_subject_id);
            }
        });
    }

    // 4. FETCH SUBJECTS VIA CUSTOM ENDPOINT
    function loadSubjects(class_id, section_id, selected_subject = null) {
        if(!class_id || !section_id) return;
        $('#modal_subject_id').html('<option value="">Loading...</option>');
        $.get(url + '/routine-modal-get-subject?class_id=' + class_id + '&section_id=' + section_id, function(data) {
            var options = '<option value="">Select Subject</option>';
            $.each(data, function(i, item) {
                let isSelected = (selected_subject == item.id) ? 'selected' : '';
                options += `<option value="${item.id}" ${isSelected}>${item.subject_name}</option>`;
            });
            $('#modal_subject_id').html(options);

            // Autoload teacher jika Edit
            if(selected_subject && edit_teacher_id) {
                loadTeachers(class_id, section_id, selected_subject, edit_teacher_id);
            }
        });
    }

    // 5. FETCH TEACHERS VIA CUSTOM ENDPOINT
    function loadTeachers(class_id, section_id, subject_id, selected_teacher = null) {
        if(!subject_id) return;
        $('#modal_teacher_id').html('<option value="">Loading...</option>');
        $.get(url + `/routine-modal-get-teacher?class_id=${class_id}&section_id=${section_id}&subject_id=${subject_id}`, function(data) {
            var options = '<option value="">Select Teacher</option>';
            $.each(data, function(i, item) {
                let isSelected = (selected_teacher == item.id) ? 'selected' : '';
                options += `<option value="${item.id}" ${isSelected}>${item.full_name}</option>`;
            });
            $('#modal_teacher_id').html(options);
        });
    }

    // --- BIND CHAINING EVENTS ---
    $('#modal_academic_year').on('change', function() { loadClasses($(this).val()); });
    $('#modal_class_id').on('change', function() { loadSections($(this).val()); });
    $('#modal_section_id').on('change', function() { loadSubjects($('#modal_class_id').val(), $(this).val()); });
    $('#modal_subject_id').on('change', function() { loadTeachers($('#modal_class_id').val(), $('#modal_section_id').val(), $(this).val()); });

    // --- TRIGGER INIT LOAD ---
    if(edit_academic_id) {
        loadClasses(edit_academic_id, edit_class_id);
    }

    // --- SUBMIT FORM AJAX AMAN ---
    $('#dynamicRoutineForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = form.serialize();
        
        form.find('.modal-btn-save').prop('disabled', true).text('Saving...');

        $.ajax({
            type: "POST",
            url: form.attr('action'),
            data: formData,
            success: function(response) {
                toastr.success('Routine saved successfully', 'Success');
                $('#addEditRoutineModal').modal('hide');
                loadMasterMatrix(); // Reload matrix (dari class_routine_new.blade.php)
            },
            error: function(err) {
                let msg = err.responseJSON && err.responseJSON.error ? err.responseJSON.error : 'Failed to save routine. Please check conflicts.';
                toastr.error(msg, 'Error');
                form.find('.modal-btn-save').prop('disabled', false).text('Save Routine');
            }
        });
    });
});
</script>