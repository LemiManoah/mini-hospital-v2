declare global {
    interface Function {
        form: (...args: any[]) => {
            action: string;
            method: 'get' | 'post' | 'put' | 'delete' | 'patch';
        };
    }
}

export {};
