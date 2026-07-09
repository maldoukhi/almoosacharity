<?php

namespace App\Support;

use App\Models\Aid;
use Illuminate\Http\Response;
use Mpdf\Mpdf;

/**
 * Renders the official aid receipt (سند صرف إعانة) through mPDF, which —
 * unlike dompdf — implements the bidi algorithm, Arabic glyph shaping and
 * true RTL layout mirroring (tables flow right-to-left), so the document
 * reads correctly with no pre-shaping tricks.
 */
class AidReceiptPdf
{
    /**
     * Build the receipt and return it for INLINE viewing in the browser
     * (the user previews it; the viewer's own controls offer download/print).
     */
    public static function response(Aid $aid): Response
    {
        $maskedNationalId = self::maskNationalId($aid->beneficiary?->national_id);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'default_font_size' => 12,
            'default_font' => 'ibmplexarabic',
            'margin_top' => 14,
            'margin_bottom' => 20,
            'margin_left' => 12,
            'margin_right' => 12,
            'tempDir' => storage_path('app/mpdf'),
            'fontDir' => [storage_path('fonts')],
            'fontdata' => [
                'ibmplexarabic' => [
                    'R' => 'IBMPlexSansArabic-Regular.ttf',
                    'B' => 'IBMPlexSansArabic-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
            ],
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->SetTitle(__('aids.receipt.title').' — '.$aid->reference);

        $mpdf->SetHTMLFooter(
            '<div style="border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #6b7280; text-align: center;">'
            .'{PAGENO} — '.e(__('aids.receipt.footer_note'))
            .'</div>'
        );

        $mpdf->WriteHTML(view('pdf.aid-receipt', [
            'aid' => $aid,
            'maskedNationalId' => $maskedNationalId,
        ])->render());

        return response($mpdf->Output('', 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-'.$aid->reference.'.pdf"',
        ]);
    }

    /**
     * Partial mask matching the national-id masking used in the reports:
     * first two + last two digits visible.
     */
    private static function maskNationalId(?string $nationalId): ?string
    {
        if (! $nationalId) {
            return null;
        }

        return substr($nationalId, 0, 2).'••••••'.substr($nationalId, -2);
    }
}
