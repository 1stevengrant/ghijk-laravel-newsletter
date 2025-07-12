import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function Home() {
    const [email, setEmail] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setMessage(null);

        try {
            const formData = new FormData();
            formData.append('email', email);

            const response = await fetch('/newsletter/iorT2peq/subscribe', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ type: 'success', text: data.message });
                setEmail('');
            } else {
                throw new Error(data.message || 'An error occurred');
            }
        } catch (error) {
            setMessage({
                type: 'error',
                text: error instanceof Error ? error.message : 'An error occurred. Please try again.'
            });
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="min-h-screen bg-[#faf6eb] text-slate-900 antialiased">
            {/* Nav */}
            <header className="flex items-center justify-between px-6 py-4 max-w-7xl mx-auto">
      <span className="font-serif text-3xl font-bold tracking-tight flex items-center gap-2">
        {/* Icon */}
          <svg viewBox="0 0 24 24" className="w-8 h-8 text-amber-600">
          <path
              fill="currentColor"
              d="M6 2h12a2 2 0 0 1 2 2v6.5l-2-1.5V4H6v16h6.25l1.5 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Z"
          />
          <path
              fill="currentColor"
              d="M21 11v9.5l-3-2-3 2V11l3 2 3-2Z"
          />
        </svg>
        Selamail
      </span>

                <nav className="flex items-center gap-6 text-sm">
                    <Link href={route('login')} className="hover:underline">Log&nbsp;in</Link>
                    <a
                        href="/signup"
                        className="rounded-full bg-amber-600 px-4 py-2 font-medium text-[#faf6eb] hover:bg-amber-700 transition"
                    >
                        Get&nbsp;started
                    </a>
                </nav>
            </header>

            {/* Hero */}
            <main className="px-6 pb-24 mt-24">
                <section className="max-w-3xl mx-auto text-center">
                    <h1 className="font-serif text-5xl md:text-6xl font-bold leading-tight tracking-tight">
                        Where messages<br className="hidden md:block" /> meet the moment
                    </h1>
                    <p className="mt-6 text-lg md:text-xl text-amber-700">
                        Build your newsletter, dispatch, or letter with Selamail — words worth pausing for.
                    </p>

                    <form
                        onSubmit={handleSubmit}
                        className="mt-10 w-full max-w-lg mx-auto"
                    >
                        <div className="flex">
                            <input
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="Enter your email"
                                required
                                disabled={isSubmitting}
                                className="flex-grow rounded-l-lg border border-slate-300 bg-white px-4 py-3 text-base outline-none focus:border-amber-600 disabled:opacity-50"
                            />
                            <button
                                type="submit"
                                disabled={isSubmitting}
                                className="rounded-r-lg bg-amber-600 px-6 py-3 text-base font-semibold text-[#faf6eb] hover:bg-amber-700 transition hover:cursor-pointer border-2 border-amber-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {isSubmitting ? 'Subscribing...' : 'Get started'}
                            </button>
                        </div>
                        
                        {message && (
                            <div className={`mt-4 p-3 rounded-lg text-sm ${
                                message.type === 'success' 
                                    ? 'bg-green-100 text-green-800 border border-green-200' 
                                    : 'bg-red-100 text-red-800 border border-red-200'
                            }`}>
                                {message.text}
                            </div>
                        )}
                    </form>
                </section>

                {/* Value Prop */}
                <section className="mt-24 max-w-2xl mx-auto">
                    <h2 className="font-serif text-4xl font-semibold text-slate-900">
                        A newsletter platform for meaningful words
                    </h2>
                    <p className="mt-4 text-lg leading-7">
                        Selamail helps you focus on writing and sharing letters that carry weight.
                        Find a moment of stillness — then press send and reach your audience
                        with tools designed to elevate your message.
                    </p>
                </section>
            </main>
        </div>
    );
}
