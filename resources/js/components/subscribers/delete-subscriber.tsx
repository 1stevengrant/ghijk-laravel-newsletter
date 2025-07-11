import { useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

import { Button } from '@/components/ui/button';

import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';


export default function DeleteSubscriber({ subscriber }: { subscriber: App.Data.NewsletterSubscriberData }) {
    const [open, setOpen] = useState(false);
    const { delete: destroy, processing } = useForm();

    const deleteSubscriber: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('subscribers.destroy', subscriber.id), {
            onSuccess: () => setOpen(false)
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm">Delete</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Are you sure you want to delete
                    "{subscriber.first_name} {subscriber.last_name}"?</DialogTitle>
                <DialogDescription>
                    Once this subscriber is deleted, all of their data will be permanently deleted. This action cannot
                    be undone.
                </DialogDescription>
                <form onSubmit={deleteSubscriber}>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            variant="destructive"
                            disabled={processing}
                            type="submit"
                        >
                            {processing ? 'Deleting...' : 'Delete subscriber'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
