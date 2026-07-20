<?php

declare(strict_types=1);

namespace App\Services\ATS;

use App\Models\Candidate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * ResumeParserService
 *
 * Simulates a third-party resume parsing API (e.g., Sovren / Textkernel).
 *
 * MOCK API CONTRACT
 * ─────────────────
 * Input:  PDF or DOCX file (UploadedFile)
 * Output: Structured JSON payload
 *
 * {
 *   "status": "success",
 *   "data": {
 *     "first_name":       string,
 *     "last_name":        string,
 *     "email":            string,
 *     "phone":            string,
 *     "skills":           string[],
 *     "experience_years": float,
 *     "education": [
 *       {
 *         "degree":          string,
 *         "institution":     string,
 *         "year_of_passing": int
 *       }
 *     ]
 *   }
 * }
 *
 * DPDP / PII SAFETY
 * ─────────────────
 * Per the DPDP compliance mandate, this service MUST NOT write extracted PII
 * (email addresses, phone numbers) to the application log. Only non-PII
 * metadata (file name, file size, candidate UUID) is logged.
 */
class ResumeParserService
{
    /**
     * Parse a resume file and attach it to the candidate via Spatie MediaLibrary.
     *
     * @param  UploadedFile  $file       The uploaded PDF or DOCX resume.
     * @param  Candidate     $candidate  The candidate model to attach the resume to.
     * @return array{status: string, data: array<string, mixed>}
     */
    public function parse(UploadedFile $file, Candidate $candidate): array
    {
        // ── 1. Store via Spatie MediaLibrary (collection: 'resumes') ──────────
        // Consistent with Phase 1 document handling.
        $candidate
            ->addMedia($file)
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection('resumes');

        // ── 2. Log NON-PII metadata only ──────────────────────────────────────
        // DPDP mandate: email and phone MUST NOT appear in logs.
        Log::info('ATS: Resume uploaded for parsing', [
            'candidate_uuid' => $candidate->candidate_id,
            'file_name'      => $file->getClientOriginalName(),
            'file_size_kb'   => round($file->getSize() / 1024, 2),
        ]);

        // ── 3. Invoke mock parser ─────────────────────────────────────────────
        // In production, replace this with an actual HTTP call to Sovren/Textkernel.
        $parsed = $this->callMockParserApi($file);

        // ── 4. Cache parsed skills/experience on the candidate record ─────────
        // Store only non-sensitive structured data; raw PII is NOT persisted here.
        $candidate->update([
            'parsed_skills'     => $parsed['data']['skills'],
            'parsed_experience' => $parsed['data']['experience_years'],
        ]);

        return $parsed;
    }

    /**
     * Simulate the third-party parser API response.
     *
     * In production this would be an HTTP POST to the Sovren/Textkernel endpoint.
     * The mock returns deterministic data derived from the file name so tests
     * can assert on specific values without network calls.
     *
     * @param  UploadedFile  $file
     * @return array{status: string, data: array<string, mixed>}
     */
    private function callMockParserApi(UploadedFile $file): array
    {
        // Derive a deterministic name from the file name for test predictability
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $parts    = explode('_', $baseName);

        $firstName = ucfirst($parts[0] ?? 'John');
        $lastName  = ucfirst($parts[1] ?? 'Doe');

        return [
            'status' => 'success',
            'data'   => [
                'first_name'       => $firstName,
                'last_name'        => $lastName,
                // Mock email derived from name — NOT the candidate's real email
                'email'            => strtolower("{$firstName}.{$lastName}@parsed.mock"),
                'phone'            => '+91' . str_pad((string) abs(crc32($baseName) % 9000000000 + 1000000000), 10, '0', STR_PAD_LEFT),
                'skills'           => ['PHP', 'Laravel', 'MySQL', 'JavaScript', 'REST APIs'],
                'experience_years' => 4.5,
                'education'        => [
                    [
                        'degree'          => 'B.Tech Computer Science',
                        'institution'     => 'Mock University',
                        'year_of_passing' => 2018,
                    ],
                ],
            ],
        ];
    }
}
