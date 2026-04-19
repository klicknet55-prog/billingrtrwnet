# Internal Announcement — Release 2026.4.18

Tim, rilis 2026.4.18 sudah tersedia di branch utama.

Poin penting:
- Billing sekarang punya mode aman `disable_invoice_attribute`.
- Ada toggle admin untuk kontrol mode tersebut tanpa perubahan manual di kode.
- Updater SQL sudah disiapkan untuk rollout konfigurasi.
- Alur messaging (single/bulk) ditingkatkan, termasuk pembaruan string EN/ID.
- Template order dan payment gateway customer sudah dirapikan.
- Aset branding (logo/favicon) sudah diperbarui.

Dampak operasional:
- Tidak ada breaking change besar.
- Disarankan validasi setting `disable_invoice_attribute` pasca deploy.

Checklist setelah deploy:
1. Uji order/recharge/reminder/message secara end-to-end.
2. Hard refresh browser agar aset baru termuat.
3. Monitor log aplikasi pada 24 jam pertama.
