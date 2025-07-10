import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

import HeadingSmall from '@/components/heading-small';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export default function DeleteList() {
    const listNameInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm<Required<{ name: string }>>({ name: '' });

    const deleteList: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('lists.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive">Delete list</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Are you sure you want to delete this list?</DialogTitle>
                <DialogDescription>
                    Once this list is deleted, all of its resources and data will also be permanently deleted. Please enter your list name
                    to confirm you would like to permanently delete this list.
                </DialogDescription>
                <form className="space-y-6" onSubmit={deleteList}>
                    <div className="grid gap-2">
                        <Label htmlFor="name" className="sr-only">
                            List Name
                        </Label>

                        <Input
                            id="name"
                            name="name"
                            ref={listNameInput}
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="list name"
                        />

                        <InputError message={errors.name} />
                    </div>

                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary" onClick={closeModal}>
                                Cancel
                            </Button>
                        </DialogClose>

                        <Button variant="destructive" disabled={processing} asChild>
                            <button type="submit">Delete list</button>
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
