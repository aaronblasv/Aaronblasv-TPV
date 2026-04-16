export interface Log {
  uuid: string;
  action: string;
  entity_type: string;
  entity_uuid: string;
  context: Record<string, unknown>;
  ip: string | null;
  created_at: string;
  user_name?: string;
}
