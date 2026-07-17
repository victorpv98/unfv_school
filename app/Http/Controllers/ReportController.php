<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Level;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\Teacher;
use App\Models\TeacherEvaluation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReportController extends Controller
{
    private const REPORTS = [
        'students' => 'Alumnos',
        'guardians' => 'Apoderados',
        'teachers' => 'Docentes',
        'enrollments' => 'Matrículas',
        'payments' => 'Pagos',
        'evaluations' => 'Calificaciones',
    ];

    public function index(Request $request): View
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria', 'docente'), 403);

        $report = $this->reportKey($request);
        $filters = $this->filters($request);
        $data = $this->buildReport($report, $filters, false);

        return view('reports.index', [
            'reports' => $this->availableReports(),
            'report' => $report,
            'title' => self::REPORTS[$report],
            'headers' => $data['headers'],
            'rows' => $data['rows'],
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function export(Request $request, string $format): Response
    {
        abort_unless(Auth::user()->hasRole('administrador', 'secretaria', 'docente'), 403);
        abort_unless(in_array($format, ['excel', 'pdf'], true), 404);

        $report = $this->reportKey($request);
        $filters = $this->filters($request);
        $data = $this->buildReport($report, $filters, true);
        $title = self::REPORTS[$report];
        $filename = Str::slug($title.' '.now()->format('Ymd-His'));

        if ($format === 'excel') {
            return response($this->xlsxWorkbook($title, $data['headers'], $data['rows']), 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => "attachment; filename=\"{$filename}.xlsx\"",
            ]);
        }

        return response($this->pdfTable($title, $data['headers'], $data['rows']), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}.pdf\"",
        ]);
    }

    private function reportKey(Request $request): string
    {
        $report = $request->string('report', 'students')->toString();
        $availableReports = $this->availableReports();

        return array_key_exists($report, $availableReports) ? $report : array_key_first($availableReports);
    }

    /**
     * @return array<string, string>
     */
    private function availableReports(): array
    {
        if (Auth::user()->hasRole('docente')) {
            return ['evaluations' => self::REPORTS['evaluations']];
        }

        return self::REPORTS;
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request): array
    {
        return $request->validate([
            'report' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:30'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'level_id' => ['nullable', 'integer', 'exists:levels,id'],
            'grade_id' => ['nullable', 'integer', 'exists:grades,id'],
            'section' => ['nullable', 'in:A,B,C'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'evaluator_type' => ['nullable', 'in:alumno,apoderado'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    private function buildReport(string $report, array $filters, bool $export): array
    {
        return match ($report) {
            'guardians' => $this->guardiansReport($filters, $export),
            'teachers' => $this->teachersReport($filters, $export),
            'enrollments' => $this->enrollmentsReport($filters, $export),
            'payments' => $this->paymentsReport($filters, $export),
            'evaluations' => $this->evaluationsReport($filters, $export),
            default => $this->studentsReport($filters, $export),
        };
    }

    private function studentsReport(array $filters, bool $export): array
    {
        $rows = Student::query()
            ->with(['guardians', 'enrollments.academicYear', 'enrollments.level', 'enrollments.grade'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function ($subquery) use ($q) {
                $subquery->where('code', 'like', "%{$q}%")
                    ->orWhere('first_names', 'like', "%{$q}%")
                    ->orWhere('last_names', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%");
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['academic_year_id'] ?? null, fn (Builder $query, int $yearId) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('academic_year_id', $yearId)))
            ->when($filters['level_id'] ?? null, fn (Builder $query, int $levelId) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('level_id', $levelId)))
            ->when($filters['grade_id'] ?? null, fn (Builder $query, int $gradeId) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('grade_id', $gradeId)))
            ->when($filters['section'] ?? null, fn (Builder $query, string $section) => $query->whereHas('enrollments', fn ($enrollment) => $enrollment->where('section', $section)))
            ->orderBy('last_names')
            ->limit($export ? 2000 : 100)
            ->get()
            ->map(function (Student $student): array {
                $enrollment = $student->enrollments->sortByDesc('academic_year_id')->first();

                return [
                    $student->code,
                    trim("{$student->first_names} {$student->last_names}"),
                    $student->dni,
                    $student->gender ?? '-',
                    $enrollment?->academicYear?->year ?? '-',
                    $enrollment?->level?->name ?? '-',
                    $enrollment?->grade?->name ?? '-',
                    $enrollment?->section ?? '-',
                    ucfirst($student->status),
                    $student->guardians->map(fn (Guardian $guardian) => trim("{$guardian->first_names} {$guardian->last_names} ({$guardian->pivot->relationship})"))->join(', ') ?: 'Sin apoderado',
                ];
            })
            ->all();

        return ['headers' => ['Código', 'Alumno', 'DNI', 'Género', 'Año', 'Nivel', 'Grado', 'Sección', 'Estado', 'Apoderados'], 'rows' => $rows];
    }

    private function guardiansReport(array $filters, bool $export): array
    {
        $rows = Guardian::query()
            ->with('students')
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function ($subquery) use ($q) {
                $subquery->where('first_names', 'like', "%{$q}%")
                    ->orWhere('last_names', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('last_names')
            ->limit($export ? 2000 : 100)
            ->get()
            ->map(fn (Guardian $guardian): array => [
                trim("{$guardian->first_names} {$guardian->last_names}"),
                $guardian->dni,
                $guardian->relationship ?? '-',
                $guardian->phone ?? '-',
                $guardian->email ?? '-',
                ucfirst($guardian->status),
                $guardian->students->map(fn (Student $student) => "{$student->code} - {$student->first_names} {$student->last_names}")->join(', ') ?: 'Sin alumnos',
            ])
            ->all();

        return ['headers' => ['Apoderado', 'DNI', 'Parentesco', 'Teléfono', 'Correo', 'Estado', 'Alumnos'], 'rows' => $rows];
    }

    private function teachersReport(array $filters, bool $export): array
    {
        $rows = Teacher::query()
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->where(function ($subquery) use ($q) {
                $subquery->where('code', 'like', "%{$q}%")
                    ->orWhere('first_names', 'like', "%{$q}%")
                    ->orWhere('last_names', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%")
                    ->orWhere('specialty', 'like', "%{$q}%");
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderBy('last_names')
            ->limit($export ? 2000 : 100)
            ->get()
            ->map(fn (Teacher $teacher): array => [
                $teacher->code,
                trim("{$teacher->first_names} {$teacher->last_names}"),
                $teacher->dni,
                $teacher->specialty ?? '-',
                $teacher->phone ?? '-',
                $teacher->email ?? '-',
                ucfirst($teacher->status),
            ])
            ->all();

        return ['headers' => ['Código', 'Docente', 'DNI', 'Especialidad', 'Teléfono', 'Correo', 'Estado'], 'rows' => $rows];
    }

    private function enrollmentsReport(array $filters, bool $export): array
    {
        $rows = Enrollment::query()
            ->with(['student', 'guardian', 'academicYear', 'level', 'grade'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->whereHas('student', function ($student) use ($q) {
                $student->where('code', 'like', "%{$q}%")
                    ->orWhere('first_names', 'like', "%{$q}%")
                    ->orWhere('last_names', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%");
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['academic_year_id'] ?? null, fn (Builder $query, int $yearId) => $query->where('academic_year_id', $yearId))
            ->when($filters['level_id'] ?? null, fn (Builder $query, int $levelId) => $query->where('level_id', $levelId))
            ->when($filters['grade_id'] ?? null, fn (Builder $query, int $gradeId) => $query->where('grade_id', $gradeId))
            ->when($filters['section'] ?? null, fn (Builder $query, string $section) => $query->where('section', $section))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('enrolled_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('enrolled_at', '<=', $date))
            ->latest('id')
            ->limit($export ? 2000 : 100)
            ->get()
            ->map(fn (Enrollment $enrollment): array => [
                $enrollment->academicYear?->year ?? '-',
                $enrollment->student ? "{$enrollment->student->code} - {$enrollment->student->first_names} {$enrollment->student->last_names}" : '-',
                $enrollment->guardian ? "{$enrollment->guardian->first_names} {$enrollment->guardian->last_names}" : '-',
                $enrollment->level?->name ?? '-',
                $enrollment->grade?->name ?? '-',
                $enrollment->section,
                $enrollment->enrolled_at?->format('d/m/Y') ?? '-',
                ucfirst($enrollment->status),
            ])
            ->all();

        return ['headers' => ['Año', 'Alumno', 'Apoderado', 'Nivel', 'Grado', 'Sección', 'Fecha', 'Estado'], 'rows' => $rows];
    }

    private function paymentsReport(array $filters, bool $export): array
    {
        $rows = StudentPayment::query()
            ->with(['student', 'paymentConcept.academicYear'])
            ->when($filters['q'] ?? null, fn (Builder $query, string $q) => $query->whereHas('student', function ($student) use ($q) {
                $student->where('code', 'like', "%{$q}%")
                    ->orWhere('first_names', 'like', "%{$q}%")
                    ->orWhere('last_names', 'like', "%{$q}%")
                    ->orWhere('dni', 'like', "%{$q}%");
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['academic_year_id'] ?? null, fn (Builder $query, int $yearId) => $query->whereHas('paymentConcept', fn ($concept) => $concept->where('academic_year_id', $yearId)))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('due_date', '<=', $date))
            ->latest('id')
            ->limit($export ? 3000 : 100)
            ->get()
            ->map(fn (StudentPayment $payment): array => [
                $payment->paymentConcept?->academicYear?->year ?? '-',
                $payment->student ? "{$payment->student->code} - {$payment->student->first_names} {$payment->student->last_names}" : '-',
                $payment->paymentConcept?->name ?? '-',
                $payment->due_date?->format('d/m/Y') ?? '-',
                'S/ '.number_format((float) ($payment->original_amount ?: $payment->amount), 2),
                'S/ '.number_format((float) $payment->late_fee_amount, 2),
                'S/ '.number_format((float) ($payment->total_amount ?: $payment->amount), 2),
                'S/ '.number_format((float) $payment->amount_paid, 2),
                ucfirst($payment->status),
                $payment->receipt_number ?? '-',
            ])
            ->all();

        return ['headers' => ['Año', 'Alumno', 'Concepto', 'Vencimiento', 'Original', 'Mora', 'Total', 'Pagado', 'Estado', 'Recibo'], 'rows' => $rows];
    }

    private function evaluationsReport(array $filters, bool $export): array
    {
        $teacher = Auth::user()->hasRole('docente')
            ? Teacher::where('user_id', Auth::id())->first()
            : null;

        $rows = TeacherEvaluation::query()
            ->with(['period', 'teacher', 'student', 'guardian', 'user'])
            ->when($teacher, fn (Builder $query) => $query->where('teacher_id', $teacher->id))
            ->when($filters['teacher_id'] ?? null, fn (Builder $query, int $teacherId) => $query->where('teacher_id', $teacherId))
            ->when($filters['evaluator_type'] ?? null, fn (Builder $query, string $type) => $query->where('evaluator_type', $type))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest('id')
            ->limit($export ? 3000 : 100)
            ->get()
            ->map(fn (TeacherEvaluation $evaluation): array => [
                $evaluation->period?->name ?? '-',
                $evaluation->teacher ? "{$evaluation->teacher->first_names} {$evaluation->teacher->last_names}" : '-',
                ucfirst($evaluation->evaluator_type),
                $evaluation->student ? "{$evaluation->student->code} - {$evaluation->student->first_names} {$evaluation->student->last_names}" : '-',
                $evaluation->guardian ? "{$evaluation->guardian->first_names} {$evaluation->guardian->last_names}" : '-',
                number_format((float) $evaluation->average_score, 2),
                $evaluation->comment ?? '-',
                $evaluation->created_at?->format('d/m/Y') ?? '-',
            ])
            ->all();

        return ['headers' => ['Periodo', 'Docente', 'Evaluador', 'Alumno', 'Apoderado', 'Promedio', 'Comentario', 'Fecha'], 'rows' => $rows];
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            'years' => AcademicYear::orderByDesc('year')->get(),
            'levels' => Level::orderBy('name')->get(),
            'grades' => Grade::with('level')->orderBy('id')->get(),
            'teachers' => Teacher::where('status', 'activo')->orderBy('last_names')->get(),
            'sections' => ['A', 'B', 'C'],
            'statuses' => ['activo', 'inactivo', 'retirado', 'pendiente', 'matriculado', 'observado', 'anulado', 'pagado', 'parcial', 'vencido'],
        ];
    }

    private function xlsxWorkbook(string $title, array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'report-xlsx-');
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', $this->xlsxContentTypes());
        $zip->addFromString('_rels/.rels', $this->xlsxRootRels());
        $zip->addFromString('xl/workbook.xml', $this->xlsxWorkbookXml($title));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->xlsxWorkbookRels());
        $zip->addFromString('xl/styles.xml', $this->xlsxStyles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->xlsxSheet($title, $headers, $rows));
        $zip->close();

        $contents = file_get_contents($path);
        unlink($path);

        return $contents;
    }

    private function xlsxContentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function xlsxRootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function xlsxWorkbookXml(string $title): string
    {
        $sheetName = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $title) ?: 'Reporte';
        $sheetName = Str::limit($sheetName, 31, '');

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.$this->xmlEscape($sheetName).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function xlsxWorkbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function xlsxStyles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="2"><font/><font><b/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEEF4FD"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/></cellXfs>'
            .'</styleSheet>';
    }

    private function xlsxSheet(string $title, array $headers, array $rows): string
    {
        $columnCount = max(1, count($headers));
        $lastColumn = $this->xlsxColumnName($columnCount);
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetData>';

        $xml .= '<row r="1"><c r="A1" t="inlineStr" s="2"><is><t>'.$this->xmlEscape($title).'</t></is></c></row>';
        $xml .= '<row r="2">';
        foreach ($headers as $index => $header) {
            $cell = $this->xlsxColumnName($index + 1).'2';
            $xml .= '<c r="'.$cell.'" t="inlineStr" s="1"><is><t>'.$this->xmlEscape($header).'</t></is></c>';
        }
        $xml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 3;
            $xml .= '<row r="'.$rowNumber.'">';
            foreach ($row as $cellIndex => $value) {
                $cell = $this->xlsxColumnName($cellIndex + 1).$rowNumber;
                $xml .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xmlEscape((string) $value).'</t></is></c>';
            }
            $xml .= '</row>';
        }

        return $xml.'</sheetData><mergeCells count="1"><mergeCell ref="A1:'.$lastColumn.'1"/></mergeCells></worksheet>';
    }

    private function xlsxColumnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function xmlEscape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function pdfTable(string $title, array $headers, array $rows): string
    {
        $lines = [$title, 'Generado: '.Carbon::now()->format('d/m/Y H:i'), ''];
        $widths = array_map(fn (string $header) => max(10, min(24, strlen($header) + 2)), $headers);

        foreach ($headers as $index => $header) {
            foreach ($rows as $row) {
                $widths[$index] = min(24, max($widths[$index], min(24, strlen((string) ($row[$index] ?? '')) + 2)));
            }
        }

        $lines[] = $this->pdfLine($headers, $widths);
        $lines[] = str_repeat('-', min(155, array_sum($widths)));

        foreach ($rows as $row) {
            $lines[] = $this->pdfLine($row, $widths);
        }

        if (empty($rows)) {
            $lines[] = 'Sin resultados.';
        }

        return $this->plainTextPdf($lines);
    }

    private function pdfLine(array $cells, array $widths): string
    {
        return collect($widths)
            ->map(function (int $width, int $index) use ($cells) {
                $value = Str::ascii((string) ($cells[$index] ?? ''));
                $value = Str::limit($value, $width - 1, '');

                return str_pad($value, $width);
            })
            ->join('');
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function plainTextPdf(array $lines): string
    {
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pages = [];
        $chunks = array_chunk($lines, 38);

        foreach ($chunks as $pageIndex => $chunk) {
            $content = "BT\n/F1 8 Tf\n50 560 Td\n";

            foreach ($chunk as $lineIndex => $line) {
                if ($lineIndex > 0) {
                    $content .= "0 -13 Td\n";
                }

                $content .= '('.$this->pdfEscape($line).") Tj\n";
            }

            $content .= "ET";
            $stream = "<< /Length ".strlen($content)." >>\nstream\n{$content}\nendstream";
            $contentObjectNumber = count($objects) + 1;
            $objects[] = $stream;
            $pageObjectNumber = count($objects) + 1;
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObjectNumber} 0 R >>";
            $pages[] = "{$pageObjectNumber} 0 R";
        }

        $objects[1] = '<< /Type /Pages /Kids ['.implode(' ', $pages).'] /Count '.count($pages).' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n{$object}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    private function pdfEscape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], Str::ascii($text));
    }
}
