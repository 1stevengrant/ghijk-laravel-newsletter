declare namespace App.Data {
export type NewsletterSubscribersData = {
id: number;
email: string;
first_name: string | null;
last_name: string | null;
subscribed_at: string | null;
verification_token: string | null;
};
}
