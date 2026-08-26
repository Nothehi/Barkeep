import { DashboardPage } from '@/features/dashboard';
import type { DashboardPageProps } from '@/features/dashboard';
import { dashboard } from '@/routes';

/**
 * A wrapper rather than a re-export, unlike every other page in this
 * directory: the breadcrumb has to be attached to the component Inertia
 * renders, and a re-exported binding is not somewhere a property can be hung.
 */
export default function Dashboard(props: DashboardPageProps) {
    return <DashboardPage {...props} />;
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
