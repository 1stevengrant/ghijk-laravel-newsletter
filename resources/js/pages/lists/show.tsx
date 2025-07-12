import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Switch } from '@/components/ui/switch';
import DeleteSubscriber from '@/components/subscribers/delete-subscriber';
import { AddNewSubscriber } from '@/components/subscribers/add-new-subscriber';



export default function Show({ list }: {
    list: App.Data.NewsletterListData
}) {
    const { post: toggleStatus, processing: toggling } = useForm();

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

    const handleToggleSubscriber = (subscriberId: number) => {
        toggleStatus(route('subscribers.toggle-status', subscriberId));
    };

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
                            <TableHead>Status</TableHead>
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
                                    <div className="flex flex-col space-y-2">
                                        {subscriber.status === 'subscribed' && subscriber.subscribed_at && (
                                            <>
                                                <span className="text-sm">Subscribed</span>
                                                <span className="text-xs text-muted-foreground">
                                                    {new Date(subscriber.subscribed_at).toLocaleDateString()}
                                                </span>
                                            </>
                                        )}
                                        {subscriber.status === 'unsubscribed' && subscriber.unsubscribed_at && (
                                            <>
                                                <span className="text-sm">Unsubscribed</span>
                                                <span className="text-xs text-muted-foreground">
                                                    {new Date(subscriber.unsubscribed_at).toLocaleDateString()}
                                                </span>
                                            </>
                                        )}
                                        {subscriber.status === 'pending' && (
                                            <span className="text-sm text-muted-foreground">Pending verification</span>
                                        )}
                                        <Switch
                                            checked={subscriber.status === 'subscribed'}
                                            onCheckedChange={() => handleToggleSubscriber(subscriber.id)}
                                            disabled={toggling}
                                            aria-label={`Toggle subscription for ${subscriber.email}`}
                                        />
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="space-y-2">
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
