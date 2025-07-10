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
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';

export const AddNew = () => {
    return (
        <>
            <Dialog>
                <DialogTrigger asChild>
                    <Button>Add Subscriber</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogTitle>Add new subscriber?</DialogTitle>
                    <DialogDescription>
                        Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password
                        to confirm you would like to permanently delete your account.
                    </DialogDescription>
                    {/*<form className="space-y-6" onSubmit={deleteUser}>*/}
                    {/*    <div className="grid gap-2">*/}
                    {/*        <Label htmlFor="password" className="sr-only">*/}
                    {/*            Password*/}
                    {/*        </Label>*/}

                    {/*        <Input*/}
                    {/*            id="password"*/}
                    {/*            type="password"*/}
                    {/*            name="password"*/}
                    {/*            ref={passwordInput}*/}
                    {/*            value={data.password}*/}
                    {/*            onChange={(e) => setData('password', e.target.value)}*/}
                    {/*            placeholder="Password"*/}
                    {/*            autoComplete="current-password"*/}
                    {/*        />*/}

                    {/*        <InputError message={errors.password} />*/}
                    {/*    </div>*/}

                    {/*    <DialogFooter className="gap-2">*/}
                    {/*        <DialogClose asChild>*/}
                    {/*            <Button variant="secondary" onClick={closeModal}>*/}
                    {/*                Cancel*/}
                    {/*            </Button>*/}
                    {/*        </DialogClose>*/}

                    {/*        <Button variant="destructive" disabled={processing} asChild>*/}
                    {/*            <button type="submit">Delete account</button>*/}
                    {/*        </Button>*/}
                    {/*    </DialogFooter>*/}
                    {/*</form>*/}
                </DialogContent>
            </Dialog>
        </>
    );
};
