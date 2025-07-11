import {
    Dialog, DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import React, { useState } from 'react';

interface AddNewSubscriberProps {
    listId: number;
}

export const AddNewSubscriber = ({ listId }: AddNewSubscriberProps) => {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        first_name: '',
        last_name: '',
        email: '',
        newsletter_list_id: listId
    });

    const createSubscriber = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(route('subscribers.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset()
        });
    };

    const closeModal = () => {
        setOpen(false);
        clearErrors();
        reset();
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button>Add Subscriber</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Add New Subscriber</DialogTitle>
                <DialogDescription>
                    Add a new subscriber to this newsletter list.
                </DialogDescription>
                <form className="space-y-6" onSubmit={createSubscriber}>
                    <div className="grid gap-2">
                        <Label htmlFor="first_name" className="sr-only">
                            First Name
                        </Label>

                        <Input
                            id="first_name"
                            name="first_name"
                            value={data.first_name}
                            onChange={(e) => setData('first_name', e.target.value)}
                            placeholder="First name"
                        />

                        <InputError message={errors.first_name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="last_name" className="sr-only">
                            Last Name
                        </Label>

                        <Input
                            id="last_name"
                            name="last_name"
                            value={data.last_name}
                            onChange={(e) => setData('last_name', e.target.value)}
                            placeholder="Last name"
                        />

                        <InputError message={errors.last_name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="email" className="sr-only">
                            Email
                        </Label>

                        <Input
                            id="email"
                            name="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            placeholder="Email address"
                        />

                        <InputError message={errors.email} />
                    </div>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button disabled={processing} asChild>
                            <button type="submit">
                                {processing ? 'Adding...' : 'Add subscriber'}
                            </button>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};