export interface ValidationErrors {
  [field: string]: string;
}

export interface ApiValidationError {
  status: number;
  error?: {
    message?: string;
    errors?: Record<string, string[]>;
  };
}