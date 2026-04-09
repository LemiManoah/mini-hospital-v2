import { type LucideIcon } from 'lucide-react';

export type HeaderFact = {
    label: string;
    value: string;
};

export type SolutionCard = {
    number: string;
    title: string;
    description: string;
    icon: LucideIcon;
    cta: string;
};

export type OperationalStat = {
    label: string;
    value: string;
};

export type Testimonial = {
    quote: string;
    name: string;
    role: string;
};

export type FooterColumn = {
    title: string;
    links: string[];
};
