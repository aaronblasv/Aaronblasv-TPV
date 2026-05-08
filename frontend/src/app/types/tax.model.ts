export interface Tax {
  uuid: string;
  name: string;
  percentage: number;
}

export interface TaxFormData {
  name: string;
  percentage: number;
}
