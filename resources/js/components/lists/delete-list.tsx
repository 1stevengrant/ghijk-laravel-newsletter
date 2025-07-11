import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Trash2Icon } from 'lucide-react';

interface DeleteListProps {
    list: {
        id: number;
        name: string;
    };
}

export default function DeleteList({ list }: DeleteListProps) {
    const [open, setOpen] = useState(false);
    const listNameInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm<Required<{ name: string }>>({ name: '' });

    const deleteList: FormEventHandler = (e) => {
        e.preventDefault();

        // Check if the entered name matches the list name
        if (data.name !== list.name) {
            return;
        }

        destroy(route('lists.destroy', list.id), {
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setOpen(false);
        clearErrors();
        reset();
    };

    // Check if the name matches to enable/disable the delete button
    const isNameMatch = data.name === list.name;

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button variant="destructive" size="sm">
                    <Trash2Icon />
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Are you sure you want to delete "{list.name}"?</DialogTitle>
                <DialogDescription>
                    Once this list is deleted, all of its resources and data will also be permanently deleted. Please enter the list name
                    <strong className="font-medium text-foreground"> {list.name} </strong>
                    to confirm you would like to permanently delete this list.
                </DialogDescription>
                <form className="space-y-6" onSubmit={deleteList}>
                    <div className="grid gap-2">
                        <Label htmlFor="name">
                            Enter list name to confirm deletion:
                        </Label>

                        <Input
                            id="name"
                            name="name"
                            ref={listNameInput}
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={`Type "${list.name}" to confirm`}
                        />

                        {!isNameMatch && data.name.length > 0 && (
                            <p className="text-sm text-destructive">
                                The name doesn't match. Please type "{list.name}" exactly.
                            </p>
                        )}

                        <InputError message={errors.name} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button
                            variant="destructive"
                            disabled={processing || !isNameMatch}
                            type="submit"
                        >
                            {processing ? 'Deleting...' : 'Delete list'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
