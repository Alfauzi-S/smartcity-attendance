# Font sertifikat

File di folder ini dipakai TCPDF untuk PDF sertifikat (`*.php`, `*.z`, `*.ctg.z`).
Salinan WOFF untuk pratinjau editor ada di `public/assets/fonts/cert`.

| Font | Sumber | Lisensi |
|---|---|---|
| Sora (300, 400, 600, 700, 800) | github.com/google/fonts `ofl/sora` | SIL OFL 1.1 |
| Montserrat (400, 700, miring) | github.com/google/fonts `ofl/montserrat` | SIL OFL 1.1 |
| Cormorant Garamond (500, 700, miring) | github.com/google/fonts `ofl/cormorantgaramond` | SIL OFL 1.1 |
| Great Vibes | github.com/google/fonts `ofl/greatvibes` | SIL OFL 1.1 |
| Pinyon Script | github.com/google/fonts `ofl/pinyonscript` | SIL OFL 1.1 |

Ketebalan statis dibuat dari font variabel dengan `fontTools.varLib.instancer`, lalu dikonversi dengan
`TCPDF_FONTS::addTTFfont($ttf, 'TrueTypeUnicode', '', 32, 'resources/fonts/')`.
Teks lisensi lengkap ada di `licenses/`.
