declare namespace App.Data {
export type NewsletterListData = {
id: number;
name: string;
description: string | null;
from_email: string;
from_name: string;
subscribers: Array<App.Data.NewsletterSubscriberData>;
};
export type NewsletterSubscriberData = {
id: number;
email: string;
first_name: string | null;
last_name: string | null;
subscribed_at: string | null;
verification_token: string | null;
};
}
