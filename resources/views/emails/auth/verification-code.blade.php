<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Kode Verifikasi {{ config('branding.name') }}</title>
    </head>
    <body style="background:#f1f4f6;font-family:Arial,sans-serif;margin:0;padding:32px 16px;color:#102637">
        <table role="presentation" style="border-collapse:collapse;margin:0 auto;max-width:560px;width:100%">
            <tr>
                <td style="background:#092a47;border-radius:12px 12px 0 0;color:#fff;padding:24px 30px">
                    <strong style="font-size:18px;letter-spacing:.04em">{{ config('branding.name') }}</strong>
                    <div style="color:#53d09a;font-size:12px;font-weight:700;letter-spacing:.12em;margin-top:3px">{{ config('branding.service') }}</div>
                </td>
            </tr>
            <tr>
                <td style="background:#fff;padding:32px 30px">
                    <p style="margin:0 0 14px">Halo {{ $name }},</p>
                    <h1 style="font-size:24px;margin:0 0 12px">Verifikasi email Anda</h1>
                    <p style="color:#60717d;line-height:1.6;margin:0 0 24px">
                        Masukkan kode berikut pada aplikasi untuk menyelesaikan pendaftaran.
                    </p>
                    <div style="background:#e9f8f2;border:1px solid #b8e6d2;border-radius:10px;color:#117c56;font-size:32px;font-weight:800;letter-spacing:10px;padding:20px;text-align:center">
                        {{ $code }}
                    </div>
                    <p style="color:#60717d;font-size:13px;line-height:1.6;margin:22px 0 0">
                        Kode berlaku selama {{ $expiresInMinutes }} menit. Abaikan email ini jika Anda tidak melakukan pendaftaran.
                    </p>
                </td>
            </tr>
        </table>
    </body>
</html>
