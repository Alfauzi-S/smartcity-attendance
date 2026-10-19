-- Migrasi aditif: jam tutup registrasi (NULL = sampai akhir hari tanggal tutup). Tidak mengubah data lama.
ALTER TABLE events ADD COLUMN reg_end_time CHAR(5) NULL;
