import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
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
import ImportSubscribers from '@/components/lists/import-subscribers';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';
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

export default function Index({ lists }: {
    lists: App.Data.NewsletterListData[]
}) {
    useEcho('imports', 'ImportStarted', (e: { message: string; type: string }) => {
        toast.success(e.message);
    });

    useEcho('imports', 'ImportCompleted', (e: { message: string; type: string; should_reload: boolean }) => {
        if (e.type === 'success') {
            toast.success(e.message);
        } else {
            toast.error(e.message);
        }
        
        if (e.should_reload) {
            router.reload({ only: ['lists'] });
        }
    });


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Lists" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div className="flex gap-2">
                    <AddNewList />
                    <ImportSubscribers lists={lists} />
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
