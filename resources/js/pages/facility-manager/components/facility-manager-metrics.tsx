import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import { type FacilityManagerMetric } from '../types';

interface FacilityManagerMetricsProps {
    metrics: FacilityManagerMetric[];
}

export function FacilityManagerMetrics({
    metrics,
}: FacilityManagerMetricsProps) {
    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            {metrics.map((metric) => (
                <Card
                    key={metric.label}
                    className="border-none shadow-sm ring-1 ring-border/50"
                >
                    <CardHeader className="space-y-0 pb-2">
                        <CardDescription className="text-xs font-medium tracking-wider text-primary uppercase">
                            {metric.label}
                        </CardDescription>
                        <CardTitle className="text-3xl font-bold text-primary">
                            {metric.value}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-xs text-muted-foreground">
                            {metric.hint}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
