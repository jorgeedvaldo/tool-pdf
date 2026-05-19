<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class ConvertController extends Controller
{
    private const MAX_MB = 50;

    public function pdfToWord(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:' . (self::MAX_MB * 1024),
        ]);

        $tmpDir = $this->makeTmpDir();

        try {
            $request->file('file')->move($tmpDir, 'input.pdf');
            $inputPath  = $tmpDir . '/input.pdf';
            $outputPath = $tmpDir . '/input.docx';

            $this->runLibreOffice([
                '--infilter=writer_pdf_import',
                '--convert-to', 'docx',
                '--outdir', $tmpDir,
                $inputPath,
            ], $tmpDir);

            if (!file_exists($outputPath)) {
                return response()->json(['error' => 'Conversão falhou: ficheiro de saída não foi gerado.'], 500);
            }

            $name = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);

            return response()
                ->download($outputPath, $name . '.docx', [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ])
                ->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    public function wordToPdf(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:' . (self::MAX_MB * 1024),
        ]);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['docx', 'doc', 'odt', 'rtf'], true)) {
            return response()->json(['error' => 'Formato não suportado. Use .docx, .doc, .odt ou .rtf.'], 422);
        }

        $tmpDir = $this->makeTmpDir();

        try {
            $inputName = 'input.' . $ext;
            $file->move($tmpDir, $inputName);
            $inputPath  = $tmpDir . '/' . $inputName;
            $outputPath = $tmpDir . '/input.pdf';

            $this->runLibreOffice([
                '--convert-to', 'pdf',
                '--outdir', $tmpDir,
                $inputPath,
            ], $tmpDir);

            if (!file_exists($outputPath)) {
                return response()->json(['error' => 'Conversão falhou: ficheiro PDF não foi gerado.'], 500);
            }

            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            return response()
                ->download($outputPath, $name . '.pdf', ['Content-Type' => 'application/pdf'])
                ->deleteFileAfterSend(false);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        } finally {
            $this->scheduleTmpCleanup($tmpDir);
        }
    }

    private function runLibreOffice(array $args, string $homeDir): void
    {
        // Use an isolated LibreOffice profile per request to avoid lock conflicts
        // when multiple conversions run concurrently.
        $cmd = array_merge(
            ['libreoffice', '--headless', '--norestore', '--nofirststartwizard'],
            $args
        );

        $env = array_merge($_ENV, [
            'HOME'         => $homeDir,
            'TMPDIR'       => $homeDir,
            'UserInstallation' => 'file://' . $homeDir . '/lo_profile',
        ]);

        $process = new Process($cmd, null, $env, null, 120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(
                'LibreOffice error: ' . trim($process->getErrorOutput() ?: $process->getOutput())
            );
        }
    }

    private function makeTmpDir(): string
    {
        $dir = sys_get_temp_dir() . '/toolpdf_' . bin2hex(random_bytes(8));
        mkdir($dir, 0755, true);
        return $dir;
    }

    private function scheduleTmpCleanup(string $dir): void
    {
        register_shutdown_function(function () use ($dir) {
            if (!is_dir($dir)) return;
            foreach (new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            ) as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($dir);
        });
    }
}
