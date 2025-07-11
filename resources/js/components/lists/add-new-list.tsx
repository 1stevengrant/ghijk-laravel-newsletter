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

export const AddNewList = () => {
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset, clearErrors } = useForm({
        name: '',
        description: '',
        from_name: '',
        from_email: ''
    });

    const createList = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(route('lists.store'), {
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
                <Button>Add New List</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Add New List</DialogTitle>
                <DialogDescription>
                    Create a new newsletter list to manage your subscribers.
                </DialogDescription>
                <form className="space-y-6" onSubmit={createList}>
                    <div className="grid gap-2">
                        <Label htmlFor="name" className="sr-only">
                            List Name
                        </Label>

                        <Input
                            id="name"
                            name="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="list name"
                        />

                        <InputError message={errors.name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="description" className="sr-only">
                            Description
                        </Label>

                        <Input
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="description (optional)"
                        />

                        <InputError message={errors.description} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="from_name" className="sr-only">
                            From Name
                        </Label>

                        <Input
                            id="from_name"
                            name="from_name"
                            value={data.from_name}
                            onChange={(e) => setData('from_name', e.target.value)}
                            placeholder="from name"
                        />

                        <InputError message={errors.from_name} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="from_email" className="sr-only">
                            From Email
                        </Label>

                        <Input
                            id="from_email"
                            name="from_email"
                            value={data.from_email}
                            onChange={(e) => setData('from_email', e.target.value)}
                            placeholder="from email"
                        />

                        <InputError message={errors.from_email} />
                    </div>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button disabled={processing} asChild>
                            <button type="submit">Create list</button>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
