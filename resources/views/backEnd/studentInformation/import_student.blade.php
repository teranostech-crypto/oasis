@extends('backEnd.master')
@section('title')
    @lang('student.student_import')
@endsection
@push('css')
    <style>
        .input-right-icon button.primary-btn-small-input {
            top: 8px !important;
            right: 11px !important;
        }
        #excel-preview-table th, 
        #excel-preview-table td {
            white-space: nowrap;
            font-size: 13px;
            vertical-align: middle;
        }
    </style>
@endpush
@section('mainContent')
    <section class="sms-breadcrumb mb-20 up_breadcrumb">
        <div class="container-fluid">
            <div class="row justify-content-between">
                <h1>@lang('student.student_import')</h1>
                <div class="bc-pages">
                    <a href="{{ route('dashboard') }}">@lang('common.dashboard')</a>
                    <a href="#">@lang('student.student_admission')</a>
                    <a href="#">@lang('student.student_import')</a>
                </div>
            </div>
        </div>
    </section>
    <section class="admin-visitor-area up_st_admin_visitor">
        <div class="container-fluid p-0">
            <div class="row">
                <div class="col-lg-6">
                    <div class="main-title">
                        <h3>@lang('common.select_criteria')</h3>
                    </div>
                </div>
                <div class="offset-lg-3 col-lg-3 text-right mb-20">
                    <a href="{{ url('/public/backEnd/bulksample/students.xlsx') }}">
                        <button class="primary-btn tr-bg text-uppercase bord-rad">
                            @lang('student.download_sample_file')
                            <span class="pl ti-download"></span>
                        </button>
                    </a>
                </div>
            </div>

            {{ Form::open([
                'class' => 'form-horizontal',
                'files' => true,
                'route' => 'student_bulk_store',
                'method' => 'POST',
                'enctype' => 'multipart/form-data',
                'id' => 'student_form',
            ]) }}
            <div class="row">
                <div class="col-lg-12">


                    <div class="white-box">
                        <div class="">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="main-title">
                                        <div class="box-body">
                                            <br>
                                            1. @lang('student.point1')<br>
                                            2. @lang('student.point2')<br>
                                            3. @lang('student.point3')<br>
                                            4. @lang('student.point4')<br>

                                            5. @lang('student.point6')(
                                            @foreach ($genders as $gender)
                                                {{ $gender->id . '=' . $gender->base_setup_name . ',' }}
                                            @endforeach


                                            ).<br>
                                            6. @lang('student.point7')(
                                            @foreach ($blood_groups as $blood_group)
                                                {{ $blood_group->id . '=' . $blood_group->base_setup_name . ',' }}
                                            @endforeach
                                            ).<br>
                                            7. @lang('student.point8')(
                                            @foreach ($religions as $religion)
                                                {{ $religion->id . '=' . $religion->base_setup_name . ',' }}
                                            @endforeach
                                            ).<br>
                                            8. For relation with guardian (F=Father, M=Mother, O=Other)<br>
                                            9. Please follow this date format(2020-06-15) for Date of birth & Admission
                                            date<br>
                                            <hr>
                                        </div>
                                    </div>
                                </div>
                            </div>


                            <input type="hidden" name="url" id="url" value="{{ URL::to('/') }}">
                            <div class="row mb-40 mt-30">

                                @if (moduleStatusCheck('University'))
                                    @includeIf(
                                        'university::common.session_faculty_depart_academic_semester_level',
                                        [
                                            'hide' => ['USUB'],
                                            'required' => ['US', 'UD', 'USN', 'USL', 'UA', 'USEC'],
                                        ]
                                    )
                                    <div class="col-lg-3 mt-25">
                                        <div class="row no-gutters input-right-icon">
                                            <div class="col">
                                                <div class="primary_input">
                                                    <input
                                                        class="primary_input_field form-control {{ $errors->has('file') ? ' is-invalid' : '' }}"
                                                        type="text" id="placeholderPhoto" placeholder="Excel file"
                                                        readonly>

                                                    @if ($errors->has('file'))
                                                        <span class="text-danger">
                                                            {{ $errors->first('file') }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <button class="primary-btn-small-input" type="button">
                                                    <label class="primary-btn small fix-gr-bg"
                                                        for="photo">@lang('common.browse')</label>
                                                    <input type="file" class="d-none" name="file" id="photo">
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="col-lg-3">
                                        <div class="primary_input ">
                                            <select
                                                class="primary_select  form-control{{ $errors->has('session') ? ' is-invalid' : '' }}"
                                                name="session" id="academic_year">
                                                <option data-display="@lang('common.academic_year') *" value="">
                                                    @lang('common.academic_year') *</option>
                                                @foreach ($sessions as $session)
                                                    <option value="{{ $session->id }}"
                                                        {{ old('session') == $session->id ? 'selected' : '' }}>
                                                        {{ $session->year }}[{{ $session->title }}]</option>
                                                @endforeach
                                            </select>

                                            @if ($errors->has('session'))
                                                <span class="text-danger invalid-select" role="alert">
                                                    {{ $errors->first('session') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-lg-3">
                                        <div class="primary_input" id="class-div">
                                            <select
                                                class="primary_select  form-control{{ $errors->has('class') ? ' is-invalid' : '' }}"
                                                name="class" id="classSelectStudent">
                                                <option data-display="@lang('common.class') *" value="">
                                                    @lang('common.class') *</option>
                                            </select>
                                            <div class="pull-right loader loader_style" id="select_class_loader">
                                                <img class="loader_img_style"
                                                    src="{{ asset('public/backEnd/img/demo_wait.gif') }}" alt="loader">
                                            </div>

                                            @if ($errors->has('class'))
                                                <span class="text-danger invalid-select" role="alert">
                                                    {{ $errors->first('class') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                        <div class="primary_input" id="sectionStudentDiv">
                                            <select
                                                class="primary_select  form-control{{ $errors->has('section') ? ' is-invalid' : '' }}"
                                                name="section" id="sectionSelectStudent">
                                                <option data-display="@lang('common.section') *" value="">
                                                    @lang('common.section') *</option>
                                            </select>
                                            <div class="pull-right loader loader_style" id="select_section_loader">
                                                <img class="loader_img_style"
                                                    src="{{ asset('public/backEnd/img/demo_wait.gif') }}" alt="loader">
                                            </div>

                                            @if ($errors->has('section'))
                                                <span class="text-danger invalid-select" role="alert">
                                                    {{ $errors->first('section') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-lg-3">
                                       
                                        <div class="primary_input">                                           
                                            <div class="primary_file_uploader">
                                                <input
                                                        class="primary_input_field form-control {{ $errors->has('file') ? ' is-invalid' : '' }}"
                                                        type="text" id="placeholderPhoto" placeholder="Excel file"
                                                        readonly>
                                                <button class="" type="button">
                                                    <label class="primary-btn small fix-gr-bg" for="upload_content_file"><span
                                                            class="ripple rippleEffect"
                                                            style="width: 56.8125px; height: 56.8125px; top: -16.4062px; left: 10.4219px;"></span>@lang('common.browse')</label>
                                                    <input type="file" class="d-none" name="file"
                                                        id="upload_content_file">
                                                </button>
                                            </div>
                                          
                                            @if ($errors->has('file'))
                                            <span class="text-danger d-block">
                                                {{ $errors->first('file') }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif


                            </div>

                            <div class="row mt-40">
                                <div class="col-lg-12 text-center">
                                    <button type="button" id="btn-preview-excel" class="primary-btn fix-gr-bg">
                                        <span class="ti-eye"></span>
                                        Preview & @lang('student.save_bulk_students')
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{ Form::close() }}
        </div>
    </section>

    <!-- Excel Data Preview Modal -->
    <div class="modal fade admin-query" id="previewStudentModal" tabindex="-1" role="dialog" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document" style="max-width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title" id="previewModalLabel">Student Import Data Preview</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (!moduleStatusCheck('University'))
                        <div class="mb-3 p-2" style="background: #f8f9fa; border-radius: 5px;">
                            <strong>@lang('common.academic_year'):</strong> <span id="prev_session">-</span> &nbsp;|&nbsp;
                            <strong>@lang('common.class'):</strong> <span id="prev_class">-</span> &nbsp;|&nbsp;
                            <strong>@lang('common.section'):</strong> <span id="prev_section">-</span> &nbsp;|&nbsp;
                            <strong>Total Data Rows:</strong> <span id="prev_total_rows">0</span> Students
                        </div>
                    @else
                        <div class="mb-3 p-2" style="background: #f8f9fa; border-radius: 5px;">
                            <strong>Total Data Rows:</strong> <span id="prev_total_rows">0</span> Students
                        </div>
                    @endif
                    <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                        <table class="table table-bordered table-striped" id="excel-preview-table">
                            <thead class="thead-light"></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="primary-btn tr-bg" data-dismiss="modal">Cancel / Recheck</button>
                    <button type="button" id="btn-confirm-submit" class="primary-btn fix-gr-bg">
                        <span class="ti-check"></span> Confirm & @lang('student.save_bulk_students')
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- SheetJS library to read Excel files (.xlsx, .xls, .csv) in the browser -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
        $(document).ready(function () {
            // Display file name when a file is selected
            $('#upload_content_file, #photo').on('change', function () {
                if (this.files && this.files.length > 0) {
                    $('#placeholderPhoto').val(this.files[0].name);
                }
            });

            $('#btn-preview-excel').on('click', function () {
                @if (!moduleStatusCheck('University'))
                    var sessionVal = $('#academic_year').val();
                    var classVal = $('#classSelectStudent').val();
                    var sectionVal = $('#sectionSelectStudent').val();

                    if (!sessionVal || !classVal || !sectionVal) {
                        toastr.warning('Please select Academic Year, Class, and Section first.', 'Warning');
                        return;
                    }
                @endif

                var fileInput = document.getElementById('upload_content_file') || document.getElementById('photo');

                if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                    toastr.warning('Please select an Excel file first.', 'Warning');
                    return;
                }

                @if (!moduleStatusCheck('University'))
                    $('#prev_session').text($('#academic_year option:selected').text().trim());
                    $('#prev_class').text($('#classSelectStudent option:selected').text().trim());
                    $('#prev_section').text($('#sectionSelectStudent option:selected').text().trim());
                @endif

                var file = fileInput.files[0];
                var reader = new FileReader();

                reader.onload = function (e) {
                    try {
                        var data = new Uint8Array(e.target.result);
                        var workbook = XLSX.read(data, { type: 'array', cellDates: true });

                        var firstSheetName = workbook.SheetNames[0];
                        var worksheet = workbook.Sheets[firstSheetName];

                        // Convert sheet to 2D array
                        var rows = XLSX.utils.sheet_to_json(worksheet, { header: 1, raw: false, dateNF: 'yyyy-mm-dd' });

                        if (rows.length <= 1) {
                            toastr.error('The Excel file is empty or contains no student data rows.', 'Failed');
                            return;
                        }

                        var thead = $('#excel-preview-table thead');
                        var tbody = $('#excel-preview-table tbody');
                        thead.empty();
                        tbody.empty();

                        // Create Header row (Row 1 in Excel)
                        var headers = rows[0];
                        var headerTr = '<tr><th>No</th>';
                        for (var h = 0; h < headers.length; h++) {
                            headerTr += '<th>' + (headers[h] !== undefined ? headers[h] : '') + '</th>';
                        }
                        headerTr += '</tr>';
                        thead.append(headerTr);

                        // Create Data rows (Starting from Row 2 in Excel)
                        var rowNumber = 0;
                        for (var i = 1; i < rows.length; i++) {
                            // Skip if an entire row is empty
                            if (rows[i].length === 0 || rows[i].every(function(cell) { return cell === undefined || String(cell).trim() === ''; })) {
                                continue;
                            }

                            rowNumber++;
                            var bodyTr = '<tr><td>' + rowNumber + '</td>';
                            for (var j = 0; j < headers.length; j++) {
                                var cellValue = rows[i][j] !== undefined ? rows[i][j] : '';
                                bodyTr += '<td>' + cellValue + '</td>';
                            }
                            bodyTr += '</tr>';
                            tbody.append(bodyTr);
                        }

                        $('#prev_total_rows').text(rowNumber);
                        $('#previewStudentModal').modal('show');
                    } catch (err) {
                        toastr.error('Failed to read Excel file. Please make sure the file format is .xlsx, .xls, or .csv.', 'Failed');
                    }
                };

                reader.readAsArrayBuffer(file);
            });

            // Execute form submit when the Confirm button inside the modal is clicked
            $('#btn-confirm-submit').on('click', function () {
                $(this).prop('disabled', true).html('<span class="ti-reload"></span> Saving...');
                $('#student_form').submit();
            });
        });
    </script>
@endpush