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
import { Button } from '@/components/ui/button';
import { PencilLineIcon, TrashIcon } from 'lucide-react';
import DeleteList from '@/components/lists/delete-list';
import { AddNewList } from '@/components/lists/add-new-list';
import { EditList } from '@/components/lists/edit-list';

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

export default function NewsletterLists({ list }: {
    list: App.Data.NewsletterListsData
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={list.name} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">

            </div>
        </AppLayout>
    );
}
