-- Migrasi aditif: kuota peserta per event (NULL = tanpa batas). Tidak mengubah data lama.
ALTER TABLE events ADD COLUMN quota INT UNSIGNED NULL;
