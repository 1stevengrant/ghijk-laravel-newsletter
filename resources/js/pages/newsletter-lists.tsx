import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import DeleteList from '@/components/lists/delete-list';
import { AddNewList } from '@/components/lists/add-new-list';
import { EditList } from '@/components/lists/edit-list';
import { EyeIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard'
    },
    {
        title: 'Lists',
        href: '/lists'
    }
];

export default function NewsletterLists({ lists }: {
    lists: App.Data.NewsletterListData[]
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lists" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div>
                    <AddNewList />
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>From</TableHead>
                            <TableHead>Reply to</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {lists.map((list: App.Data.NewsletterListData) => (
                            <TableRow key={list.id}>
                                <TableCell>{list.name} ({list.subscribers_count})</TableCell>
                                <TableCell>
                                    {list.from_name}
                                </TableCell>
                                <TableCell>
                                    {list.from_email}
                                </TableCell>
                                <TableCell>
                                    <div className="space-x-2">
                                        <EditList list={list} />
                                        <DeleteList list={list} />
                                        <Button
                                            variant="outline"
                                            asChild>
                                            <Link href={route('lists.show', list.id)}>
                                                <EyeIcon />
                                            </Link>
                                        </Button>
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
