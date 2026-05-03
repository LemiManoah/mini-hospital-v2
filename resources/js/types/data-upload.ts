export interface ImportError {
    row: number;
    name: string;
    messages: string[];
}

export interface ImportResult {
    imported: number;
    skipped: number;
    errors: ImportError[];
}

export interface DataUploadIndexPageProps {
    importResult: ImportResult | null;
}
