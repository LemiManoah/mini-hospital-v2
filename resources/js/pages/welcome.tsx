import { WelcomeCtaSection } from '@/components/welcome/welcome-cta-section';
import { WelcomeEvidenceSection } from '@/components/welcome/welcome-evidence-section';
import { WelcomeFooter } from '@/components/welcome/welcome-footer';
import { WelcomeHeader } from '@/components/welcome/welcome-header';
import { WelcomeHeroSection } from '@/components/welcome/welcome-hero-section';
import { WelcomeProofStrip } from '@/components/welcome/welcome-proof-strip';
import { WelcomeShowcaseSection } from '@/components/welcome/welcome-showcase-section';
import { WelcomeSolutionsSection } from '@/components/welcome/welcome-solutions-section';
import { type FooterColumn, type HeaderFact, type OperationalStat, type ShowcaseShot, type SolutionCard, type Testimonial } from '@/components/welcome/types';
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

const showcaseShots: ShowcaseShot[] = [
    {
        eyebrow: 'Dashboard',
        title: 'Facility pulse in one operational view',
        description:
            'A clean daily dashboard for front-desk and admin teams, showing current visits, appointments, pending results, and the shape of the day.',
        image: '/images/welcome/dashboard-overview.png',
        accent: 'Daily pulse',
    },
    {
        eyebrow: 'Consultation',
        title: 'Consultation queue for doctor handoff',
        description:
            'Doctors can move from triage into review quickly, with patient identity, chief complaint, assignment, and action all visible at once.',
        image: '/images/welcome/consultation-queue.png',
        accent: 'Doctor workflow',
    },
    {
        eyebrow: 'Laboratory',
        title: 'Bench queue for incoming investigations',
        description:
            'Laboratory staff can pick samples, track specimen progress, and follow request timelines without losing clarity on the queue.',
        image: '/images/welcome/laboratory-queue.png',
        accent: 'Bench operations',
    },
];

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

            <div className="min-h-screen bg-stone-50 font-[Inter] text-stone-900 selection:bg-[#4A6FA5] selection:text-white dark:bg-[#0e0e0e] dark:text-stone-200">
                <WelcomeHeader authenticated={authenticated} />
                <main className="pt-0">
                    <WelcomeHeroSection
                        headerFacts={headerFacts}
                        authenticated={authenticated}
                    />
                    <WelcomeProofStrip logos={proofLogos} />
                    <WelcomeSolutionsSection solutionCards={solutionCards} />
                    <WelcomeShowcaseSection showcaseShots={showcaseShots} />

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
