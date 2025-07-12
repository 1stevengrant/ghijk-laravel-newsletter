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
import { EyeIcon, Trash2Icon } from 'lucide-react';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useState } from 'react';

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
    const [selectedLists, setSelectedLists] = useState<number[]>([]);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [confirmText, setConfirmText] = useState('');
    const [isDeleting, setIsDeleting] = useState(false);

    const toggleSelectList = (listId: number) => {
        setSelectedLists(prev => 
            prev.includes(listId) 
                ? prev.filter(id => id !== listId)
                : [...prev, listId]
        );
    };

    const toggleSelectAll = () => {
        setSelectedLists(prev => 
            prev.length === lists.length ? [] : lists.map(list => list.id)
        );
    };

    const handleBulkDelete = async () => {
        if (confirmText !== 'DELETE SELECTED') {
            toast.error('Please type "DELETE SELECTED" to confirm deletion');
            return;
        }

        setIsDeleting(true);
        try {
            await router.delete(route('lists.bulk-delete'), {
                data: { list_ids: selectedLists },
                preserveScroll: true,
                onSuccess: () => {
                    toast.success(`Successfully deleted ${selectedLists.length} list(s)`);
                    setSelectedLists([]);
                    setIsDeleteModalOpen(false);
                    setConfirmText('');
                },
                onError: () => {
                    toast.error('Failed to delete lists. Please try again.');
                }
            });
        } catch (error) {
            toast.error('An error occurred while deleting lists.');
        } finally {
            setIsDeleting(false);
        }
    };
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
                <div className="flex gap-2 justify-between">
                    <div className="flex gap-2">
                        <AddNewList />
                        <ImportSubscribers lists={lists} />
                    </div>
                    {selectedLists.length > 0 && (
                        <Dialog open={isDeleteModalOpen} onOpenChange={setIsDeleteModalOpen}>
                            <DialogTrigger asChild>
                                <Button variant="destructive" className="flex items-center gap-2">
                                    <Trash2Icon className="h-4 w-4" />
                                    Delete Selected ({selectedLists.length})
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete Selected Lists</DialogTitle>
                                    <DialogDescription>
                                        This action cannot be undone. You are about to delete {selectedLists.length} newsletter list(s) and all their subscribers.
                                    </DialogDescription>
                                </DialogHeader>
                                <div className="py-4">
                                    <label className="text-sm font-medium mb-2 block">
                                        Type "DELETE SELECTED" to confirm:
                                    </label>
                                    <Input
                                        value={confirmText}
                                        onChange={(e) => setConfirmText(e.target.value)}
                                        placeholder="DELETE SELECTED"
                                        className="w-full"
                                    />
                                </div>
                                <DialogFooter>
                                    <Button
                                        variant="outline"
                                        onClick={() => {
                                            setIsDeleteModalOpen(false);
                                            setConfirmText('');
                                        }}
                                        disabled={isDeleting}
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={handleBulkDelete}
                                        disabled={isDeleting || confirmText !== 'DELETE SELECTED'}
                                    >
                                        {isDeleting ? 'Deleting...' : 'Delete Lists'}
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    )}
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead className="w-12">
                                <Checkbox
                                    checked={selectedLists.length === lists.length && lists.length > 0}
                                    onCheckedChange={toggleSelectAll}
                                    aria-label="Select all lists"
                                />
                            </TableHead>
                            <TableHead>Name</TableHead>
                            <TableHead>From</TableHead>
                            <TableHead>Reply to</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {lists.map((list: App.Data.NewsletterListData) => (
                            <TableRow key={list.id}>
                                <TableCell>
                                    <Checkbox
                                        checked={selectedLists.includes(list.id)}
                                        onCheckedChange={() => toggleSelectList(list.id)}
                                        aria-label={`Select ${list.name}`}
                                    />
                                </TableCell>
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
