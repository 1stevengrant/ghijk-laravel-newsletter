import {
    Dialog,
    DialogClose,
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
import { PencilIcon } from 'lucide-react';


export const EditList = ({ list }: { list: App.Data.NewsletterListData }) => {
    const [open, setOpen] = useState(false);
    const { data, setData, put, processing, errors, reset, clearErrors } = useForm({
        name: list.name,
        description: list.description || '',
        from_name: list.from_name,
        from_email: list.from_email
    });

    const updateList = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        put(route('lists.update', list.id), {
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
                <Button variant="outline" size="sm">
                    <PencilIcon />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Edit List</DialogTitle>
                <DialogDescription>
                    Update the newsletter list details below.
                </DialogDescription>
                <form className="space-y-6" onSubmit={updateList}>
                    <div className="grid gap-2">
                        <Label htmlFor="name">
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
                        <Label htmlFor="description">
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
                        <Label htmlFor="from_name">
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
                        <Label htmlFor="from_email">
                            From Email
                        </Label>

                        <Input
                            id="from_email"
                            name="from_email"
                            type="email"
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

                        <Button disabled={processing} type="submit">
                            {processing ? 'Updating...' : 'Update list'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
};
