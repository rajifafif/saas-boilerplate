export interface Address {
  type: string
  name: string
  provinsi_id: string | null
  kota_id: string | null
  kelurahan_id: string | null
  kecamatan_id: string | null
  kode_pos: string | null
  text: string | null
  description: string | null
  latitude: string | null
  longitude: string | null
  full_name: string | null
  receiver_name: string | null
  receiver_phone: string | null
}
