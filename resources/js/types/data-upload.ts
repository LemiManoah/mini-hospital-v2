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

export interface DataImportSummary {
    id: string;
    importType: string;
    sourceFilename: string;
    status: 'queued' | 'previewed' | 'processing' | 'completed' | 'failed';
    importedCount: number;
    skippedCount: number;
    previewCount: number;
    failureMessage: string | null;
    policy?: string | null;
    policyId?: string | null;
    policyName?: string | null;
    createdAt: string | null;
    startedAt: string | null;
    completedAt: string | null;
    failedAt: string | null;
}

export interface DataUploadIndexPageProps {
    importResult: ImportResult | null;
    importResultMode: 'import' | 'preview';
    hasErrorReport: boolean;
    queuedImportMessage: string | null;
    dataImports: DataImportSummary[];
}
