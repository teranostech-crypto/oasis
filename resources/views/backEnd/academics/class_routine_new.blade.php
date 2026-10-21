@extends('backEnd.master')
@section('title')
    Master Class Schedule Board
@endsection
@section('mainContent')
    <section class="sms-breadcrumb mb-20">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <h1>Timetable</h1>
                <div class="bc-pages">
                    <a href="{{ route('dashboard') }}">@lang('common.dashboard')</a>
                    <a href="#">@lang('academics.academics')</a>
                    <a href="#">Schedule Matrix</a>
                </div>
            </div>
        </div>
    </section>

    <section class="admin-visitor-area">
        <div class="container-fluid p-0">
            <div class="white-box" style="border-radius: 12px; padding: 20px;">
                <div class="row">
                    <div class="col-lg-12">
                        {{ Form::open(['class' => 'form-horizontal', 'route' => 'class_routine_new', 'method' => 'get']) }}
                        <div class="row align-items-end">
                            <div class="col-lg-7">
                                <label class="primary_input_label" style="font-size: 12px; font-weight:600;">Academic Year Filter</label>
                                <select class="primary_select form-control" id="select_academic_year" name="academic_year">
                                    <option value="all" {{ (isset($academic_year_id) && $academic_year_id == 'all') ? 'selected' : '' }}>🌟 Merge All Academic Years (Cross-Check Clash)</option>
                                    @if(isset($academic_years))
                                        @foreach ($academic_years as $academic_year)
                                            <option value="{{ $academic_year->id }}" {{ ((isset($academic_year_id) && $academic_year_id == $academic_year->id) || (!isset($academic_year_id) && getAcademicId() == $academic_year->id)) ? 'selected' : '' }}>
                                                Focus on: {{ $academic_year->year }} [{{ $academic_year->title }}]
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <input type="hidden" name="routine_academic_year_id" id="routine_academic_year_id" value="{{ isset($academic_year_id) ? $academic_year_id : getAcademicId() }}">
                            </div>
                            <div class="col-lg-5 text-right d-flex justify-content-end align-items-center style-gap" style="gap: 8px;">
                                <button type="submit" class="primary-btn small fix-gr-bg" style="border-radius:6px; padding: 6px 18px;">
                                    <span class="ti-search pr-2"></span> Load
                                </button>
                                
                                <button type="button" class="primary-btn small" data-toggle="modal" data-target="#addEditRoutineModal" onclick="openAddRoutineModal()" style="border-radius:6px; padding: 6px 18px; background: #E56A63; color: white; border: none; box-shadow: 0 4px 6px rgba(229, 106, 99, 0.15); transition: all 0.3s ease;">
                                    <span class="ti-plus pr-2"></span> Add New
                                </button>
                            </div>
                        </div>
                        {{ Form::close() }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-20">
        <div class="container-fluid p-0">
            <div id="select_class_routine_loader" style="display:none; padding: 50px; background:#fff; border-radius:10px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                <img src="{{ asset('public/backEnd/img/demo_wait.gif') }}" alt="loader" style="width: 40px; display:block; margin:0 auto;">
                <p class="mt-2 text-muted text-center" style="font-size:12px;">Building single-board matrix timeline...</p>
            </div>
            <div id="show_routine"></div>
        </div>
    </section>

    <div class="modal fade" id="routineDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; background: #f8fafc; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 16px 20px;">
                    <h5 class="modal-title" id="modalSubjectName" style="font-weight: 800; color: #0f172a; font-size: 15px;">Routine Detail</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 20px; color: #94a3b8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px; background: #ffffff;">
                    <div class="mb-4">
                        <span style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700; display:block; margin-bottom: 6px; letter-spacing: 0.5px;">Time Duration</span>
                        <div style="font-size: 13.5px; font-weight: 700; color: #1e293b; background: #f1f5f9; padding: 8px 14px; border-radius: 8px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="ti-alarm-clock" style="color: #4f46e5;"></i>
                            <span id="modalTimeDuration">00:00 - 00:00</span>
                        </div>
                    </div>
                    <div id="modalClassMetaSection">
                        <div class="mb-3" style="border-bottom: 1px solid #f8fafc; padding-bottom: 12px;">
                            <span style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700; display:block; margin-bottom: 4px; letter-spacing: 0.5px;">Instructor / Teacher</span>
                            <div style="font-size: 13.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 8px;">
                                <i class="ti-user" style="color: #4f46e5; font-size: 12px;"></i> <span id="modalTeacherName">N/A</span>
                            </div>
                        </div>
                        <div class="mb-1">
                            <span style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700; display:block; margin-bottom: 4px; letter-spacing: 0.5px;">Classroom</span>
                            <div style="font-size: 13.5px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 8px;">
                                <i class="ti-location-pin" style="color: #4f46e5; font-size: 12px;"></i> <span id="modalRoomNo">N/A</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; background: #f8fafc; border-bottom-left-radius: 14px; border-bottom-right-radius: 14px; padding: 12px 20px; gap: 6px;">
                    <button type="button" class="primary-btn small text-white" style="background:#64748b; border:0; border-radius:6px; padding: 5px 14px;" data-dismiss="modal">Close</button>
                    <button type="button" class="primary-btn small fix-gr-bg" id="modalActionEdit" style="border-radius:6px; padding: 5px 14px;">
                        <i class="ti-pencil"></i> Edit
                    </button>
                    <button type="button" class="primary-btn small" id="modalActionDelete" style="border-radius:6px; padding: 5px 14px; background: #ef4444; border:0; color:white;">
                        <i class="ti-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addEditRoutineModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="border-radius: 14px; border: none; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
                <div class="modal-header" style="border-bottom: 1px solid #f1f5f9; background: #f8fafc; border-top-left-radius: 14px; border-top-right-radius: 14px; padding: 16px 24px;">
                    <h5 class="modal-title" id="addEditModalTitle" style="font-weight: 800; color: #0f172a; font-size: 16px;">Add New Data</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="font-size: 20px; color: #94a3b8;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px; background: #ffffff;">
                    <div id="addEditModalLoader" style="display:none; text-align:center; padding: 40px 0;">
                        <img src="{{ asset('public/backEnd/img/demo_wait.gif') }}" alt="loader" style="width: 40px;">
                        <p style="font-size: 12px; color: #94a3b8; margin-top: 10px;">Loading form...</p>
                    </div>
                    <div id="addEditRoutineFormContainer"></div>
                </div>
            </div>
        </div>
    </div>

    @push('script')
        <script>
            $(document).ready(function() { loadMasterMatrix(); });
            
            function loadMasterMatrix() {
                $('#show_routine').html('');
                $('#select_class_routine_loader').show();
                var url = $("#url").val();
                var academic_year_id = $('#routine_academic_year_id').val();
                $.ajax({
                    type: "post",
                    data: { academic_year_id: academic_year_id },
                    dataType: "html",
                    url: url + "/day-wise-class-routine",
                    success: function(data) {
                        $('#show_routine').html(data);
                        $('#select_class_routine_loader').hide();
                    },
                    error: function(xhr, status, error) {
                        $('#select_class_routine_loader').hide();
                        
                        // DEBUGGER AMPUH: Cetak langsung error Laravel ke dalam layar!
                        $('#show_routine').html(
                            "<div style='background:#fee2e2; padding:20px; border-radius:8px; border:1px solid #fca5a5; overflow-x:auto;'>" + 
                            "<h4 style='color:#b91c1c;'>🚨 Server Error (" + xhr.status + ")</h4>" +
                            "<div style='font-size:12px; color:#7f1d1d;'>" + xhr.responseText + "</div>" +
                            "</div>"
                        );
                        
                        console.log('AJAX Error Details:', error);
                    }
                });
            }

            function showRoutineDetails(element, event) {
                if ($(event.target).closest('.routine-actions').length) return;

                var id = $(element).data('id');
                var isBreak = $(element).data('is-break');
                var subject = $(element).data('subject');
                var teacher = $(element).data('teacher');
                var room = $(element).data('room');
                var start = $(element).data('start');
                var end = $(element).data('end');

                $('#modalSubjectName').text(subject);
                $('#modalTimeDuration').text(start + ' - ' + end);

                if (isBreak == 1) {
                    $('#modalClassMetaSection').hide();
                } else {
                    $('#modalClassMetaSection').show();
                    $('#modalTeacherName').text(teacher);
                    $('#modalRoomNo').text(room);
                }

                $('#modalActionEdit').attr('onclick', `triggerEditRoutine('${id}', event)`);
                $('#modalActionDelete').attr('onclick', `triggerDeleteRoutine('${id}', event)`);
                $('#routineDetailModal').modal('show');
            }

            // Fungsi Buka Modal Add
            function openAddRoutineModal() {
                $('#addEditModalTitle').text('Add New Data');
                $('#addEditRoutineFormContainer').html('');
                $('#addEditModalLoader').show();
                
                $.get("{{ route('add-new-routine-form-ajax') }}", function(data) {
                    $('#addEditModalLoader').hide();
                    $('#addEditRoutineFormContainer').html(data);
                });
            }

            // Fungsi Buka Modal Edit
            function triggerEditRoutine(id, event) {
                if (event) event.stopPropagation();
                $('#routineDetailModal').modal('hide');
                
                $('#addEditModalTitle').text('Edit Data');
                $('#addEditRoutineFormContainer').html('');
                $('#addEditRoutineModal').modal('show');
                $('#addEditModalLoader').show();

                $.get("{{ url('edit-routine-form-ajax') }}/" + id, function(data) {
                    $('#addEditModalLoader').hide();
                    $('#addEditRoutineFormContainer').html(data);
                });
            }

            // Fungsi Delete
            function triggerDeleteRoutine(id, event) {
                if (event) event.stopPropagation();
                $('#routineDetailModal').modal('hide');
                
                if (confirm('Are you sure you want to delete this routine?')) {
                    var url = $("#url").val();
                    $.ajax({
                        type: "POST",
                        data: { id: id },
                        url: url + "/destroy-class-routine",
                        success: function (data) {
                            toastr.success('Routine successfully deleted', 'Success');
                            loadMasterMatrix();
                        },
                        error: function () {
                            toastr.error('Failed to delete routine', 'Failed');
                        }
                    });
                }
            }
        </script>
    @endpush
@endsection

@include('backEnd.partials.date_picker_css_js')