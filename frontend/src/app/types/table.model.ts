import { Zone } from './zone.model';

export interface Table {
  uuid: string;
  name: string;
  zone_id: string;
  merged_with: string | null;
  zone?: Zone;
}

export interface TableFormData {
  name: string;
  zone_id: string;
}
