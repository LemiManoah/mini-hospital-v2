import { WelcomeCtaSection } from '@/components/welcome/welcome-cta-section';
import { WelcomeEvidenceSection } from '@/components/welcome/welcome-evidence-section';
import { WelcomeFooter } from '@/components/welcome/welcome-footer';
import { WelcomeHeader } from '@/components/welcome/welcome-header';
import { WelcomeHeroSection } from '@/components/welcome/welcome-hero-section';
import { WelcomeProofStrip } from '@/components/welcome/welcome-proof-strip';
import { WelcomeSolutionsSection } from '@/components/welcome/welcome-solutions-section';
import { type FooterColumn, type HeaderFact, type OperationalStat, type SolutionCard, type Testimonial } from '@/components/welcome/types';
import { type SharedData } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ClipboardPlus, DollarSign, FlaskConical, ShieldCheck, Stethoscope } from 'lucide-react';

const headerFacts: HeaderFact[] = [
    { label: 'Focus', value: 'Hospital Operations' },
    { label: 'Deployment', value: 'Branch-ready Platform' },
    { label: 'Product', value: 'QrooEMR' },
    { label: 'Mode', value: 'Cloud or Managed Hosting' },
];

const proofLogos = ['Front Desk', 'Triage', 'Consultation', 'Laboratory', 'Pharmacy'];

const solutionCards: SolutionCard[] = [
    {
        number: '01',
        title: 'Patient Intake',
        description:
            'Registration, visit creation, triage, and patient movement designed to stay fast during real hospital pressure.',
        icon: ClipboardPlus,
        cta: 'Front desk workflow',
    },
    {
        number: '02',
        title: 'Clinical Workspace',
        description:
            'Consultations, documentation, orders, and follow-through organized in one calm doctor-facing workspace.',
        icon: Stethoscope,
        cta: 'Consultation flow',
    },
    {
        number: '03',
        title: 'Lab and Pharmacy',
        description:
            'Specimen workflows, result release, prescriptions, inventory, and dispensing tied back to the patient timeline.',
        icon: FlaskConical,
        cta: 'Operational modules',
    },
    {
        number: '04',
        title: 'Accounting Foundations',
        description:
            'Charge capture, billing codes, and claim generation built to integrate with third-party billing systems.',
        icon: DollarSign,
        cta: 'Accounting module',
    },
    {
        number: '05',
        title: 'Inpatient Support',
        description:
            'Admission, discharge, and transfer workflows designed to streamline inpatient care.',
        icon: Stethoscope,
        cta: 'Inpatient module',
    },
    {
        number: '06',
        title: 'Administration',
        description:
            'Permissions, billing foundations, branch isolation, and reporting surfaces built for disciplined growth.',
        icon: ShieldCheck,
        cta: 'Governance layer',
    },
];

const operationalStats: OperationalStat[] = [
    { label: 'Queues in sync', value: 'Visits, lab, pharmacy' },
    { label: 'Built for', value: 'Front desk to release' },
    { label: 'Rollout style', value: 'Module by module' },
];

const testimonials: Testimonial[] = [
    {
        quote: 'The layout feels deliberate. Staff can tell where to act next without hunting through the screen.',
        name: 'Operations Lead',
        role: 'Outpatient Services',
    },
    {
        quote: 'It supports the clinical flow without turning every task into a documentation burden.',
        name: 'Medical Director',
        role: 'General Hospital Team',
    },
];

const footerColumns: FooterColumn[] = [
    {
        title: 'Platform',
        links: ['QrooEMR', 'Laboratory', 'Pharmacy', 'Billing'],
    },
    {
        title: 'Workflow',
        links: ['Registration', 'Triage', 'Consultation', 'Result Release'],
    },
    {
        title: 'Company',
        links: ['Implementation', 'Support', 'Hosting', 'Roadmap'],
    },
];

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;
    const authenticated = auth.user !== null;

    return (
        <>
            <Head title="QrooEMR | Digital Clinical Operations">
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link
                    href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-[#0e0e0e] font-[Inter] text-stone-200 selection:bg-[#4A6FA5] selection:text-white">
                <WelcomeHeader authenticated={authenticated} />
                <main className="pt-0">
                    <WelcomeHeroSection
                        headerFacts={headerFacts}
                        authenticated={authenticated}
                    />
                    <WelcomeProofStrip logos={proofLogos} />
                    <WelcomeSolutionsSection solutionCards={solutionCards} />
                    <WelcomeEvidenceSection
                        operationalStats={operationalStats}
                        testimonials={testimonials}
                    />
                    <WelcomeCtaSection />
                </main>
                <WelcomeFooter
                    footerColumns={footerColumns}
                    authenticated={authenticated}
                />
            </div>
        </>
    );
}
