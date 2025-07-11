import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import DeleteSubscriber from '@/components/subscribers/delete-subscriber';
import { AddNewSubscriber } from '@/components/subscribers/add-new-subscriber';



export default function Show({ list }: {
    list: App.Data.NewsletterListData
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard'
        },
        {
            title: 'Lists',
            href: '/lists'
        },
        {
            title: list.name,
            href: `/lists/${list.id}`
        }
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={list.name} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div>
                    <AddNewSubscriber listId={list.id} />
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Subscribed On</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {list.subscribers.map((subscriber: App.Data.NewsletterSubscriberData) => (
                            <TableRow key={subscriber.id}>
                                <TableCell>{subscriber.first_name} {subscriber.last_name}</TableCell>
                                <TableCell>
                                    {subscriber.email}
                                </TableCell>
                                <TableCell>
                                    {subscriber.subscribed_at}
                                </TableCell>
                                <TableCell>
                                    <div className="space-x-2">
                                        <DeleteSubscriber subscriber={subscriber} />
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
