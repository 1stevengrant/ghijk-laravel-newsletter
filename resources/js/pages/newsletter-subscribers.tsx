import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard'
    },
    {
        title: 'Newsletter Subscribers',
        href: '/subscribers'
    }
];
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { PauseIcon, PlayIcon, TrashIcon } from 'lucide-react';
import { AddNew } from '@/components/subscribers/add-new';

export default function NewsletterSubscribers({ newsletterSubscribers }: {
    newsletterSubscribers: App.Data.NewsletterSubscribersData[]
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Newsletter Subscribers" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <AddNew />
                <Table>
                    <TableCaption>A list of your recent invoices.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Email</TableHead>
                            <TableHead>First Name</TableHead>
                            <TableHead>Last Name</TableHead>
                            <TableHead>Subscribed On</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {newsletterSubscribers.map((subscriber: App.Data.NewsletterSubscribersData) => (
                            <TableRow key={subscriber.id}>
                                <TableCell>{subscriber.email}</TableCell>
                                <TableCell>{subscriber.first_name || 'N/A'}</TableCell>
                                <TableCell>{subscriber.last_name || 'N/A'}</TableCell>
                                <TableCell>{subscriber.subscribed_at ? new Date(subscriber.subscribed_at).toLocaleDateString() : 'Not Subscribed'}</TableCell>
                                <TableCell className="space-x-2">
                                    <Button variant="destructive">
                                        <TrashIcon />
                                    </Button>
                                    <Button>
                                        <PlayIcon />
                                        <PauseIcon />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
