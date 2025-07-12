import { Link } from '@inertiajs/react';

export default function Home() {
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
                        action="/early-access"
                        method="POST"
                        className="mt-10 flex w-full max-w-lg mx-auto"
                    >
                        <input
                            type="email"
                            name="email"
                            placeholder="Enter your email"
                            required
                            className="flex-grow rounded-l-lg border border-slate-300 bg-white px-4 py-3 text-base outline-none focus:border-amber-600"
                        />
                        <button
                            type="submit"
                            className="rounded-r-lg bg-amber-600 px-6 py-3 text-base font-semibold text-[#faf6eb] hover:bg-amber-700 transition hover:cursor-pointer border-2 border-amber-600"
                        >
                            Get started
                        </button>
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
