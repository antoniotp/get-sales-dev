import { Toaster, Toaster as Sonner } from "@/components/ui/sonner";
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { TooltipProvider } from "@/components/ui/tooltip";
import Navigation from '@/components/Navigation';
import Hero from '@/components/Hero';
import Features from '@/components/Features';
import HowItWorks from '@/components/HowItWorks';
import Testimonials from '@/components/Testimonials';
import Contact from '@/components/Contact';
import Footer from '@/components/Footer';
import { Head } from '@inertiajs/react';
import Partnerships from '@/components/Partnerships';

const queryClient = new QueryClient();

export default function Home() {
    return (
        <>
            <Head title="Home">
            </Head>
            <QueryClientProvider client={queryClient}>
                <TooltipProvider>
                    <Toaster />
                    <Sonner />
                    <div className="min-h-screen">
                        <Navigation />
                        <Hero />
                        <Features />
                        <HowItWorks />
                        <Testimonials />
                        <Partnerships />
                        <Contact />
                        <Footer />
                    </div>
                </TooltipProvider>
            </QueryClientProvider>
        </>
    );
}
