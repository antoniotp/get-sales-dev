import { MessageSquare, Users, LineChart } from 'lucide-react';

const Hero = () => {
    return (
        <section id="home" className="pt-32 pb-20 md:pt-40 md:pb-32 px-4">
            <div className="container-custom">
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div className="flex flex-col space-y-8">
                        <div>
                            <div
                                className="bg-whatsapp/10 text-whatsapp inline-flex items-center px-4 py-2 rounded-full mb-4">
                                <MessageSquare size={16} className="mr-2" />
                                <span className="text-sm font-medium">Power up your WhatsApp Business</span>
                            </div>
                            <h1 className="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-4">
                                Maximize your sales with <span className="text-brandBlue">agents</span> on WhatsApp
                            </h1>
                            <p className="text-lg text-gray-600 mb-8">
                                Connect your WhatsApp Business account and let professional agents sell for you.
                                Increase conversions, improve customer service, and scale your business without
                                complications.
                            </p>

                            <div className="flex flex-col sm:flex-row gap-4">
                                <a href="#" className="btn-primary text-base py-6 px-8 flex items-center gap-2">
                                    <span>Get started now</span>
                                </a>
                                <a href="#contact"
                                   className="btn-outline text-center py-6 px-8 inline-flex items-center justify-center gap-2">
                                    <span>Contact us</span>
                                </a>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 md:grid-cols-3 gap-6 pt-4">
                            <div className="flex items-center gap-2">
                                <Users className="text-brandBlue" />
                                <span className="text-sm font-medium">Qualified agents</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <MessageSquare className="text-whatsapp" />
                                <span className="text-sm font-medium">Quick response</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <LineChart className="text-brandBlue" />
                                <span className="text-sm font-medium">More conversions</span>
                            </div>
                        </div>
                    </div>

                    <div className="relative">
                        <div className="relative z-10">
                            <img
                                src="https://images.unsplash.com/photo-1556157382-97eda2d62296?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80"
                                alt="WhatsApp Business Agents"
                                className="rounded-lg shadow-2xl w-full"
                            />
                        </div>

                        <div
                            className="absolute bottom-12 -left-12 bg-white p-4 rounded-lg shadow-lg z-20 max-w-[260px] animate-bounce-slow hidden md:block">
                            <div className="flex items-start gap-4">
                                <div className="bg-whatsapp rounded-full p-2">
                                    <MessageSquare size={20} className="text-white" />
                                </div>
                                <div>
                                    <h3 className="font-medium text-sm dark:text-gray-900">Automatic sales</h3>
                                    <p className="text-xs text-gray-500 mt-1">Increase your sales by 35% with
                                        specialized agents</p>
                                </div>
                            </div>
                        </div>

                        <div
                            className="absolute top-10 -right-10 bg-gradient-to-br from-brandBlue to-blue-700 h-64 w-64 rounded-full opacity-20 blur-3xl -z-10"></div>
                        <div
                            className="absolute -bottom-10 -left-10 bg-gradient-to-br from-whatsapp to-green-700 h-64 w-64 rounded-full opacity-20 blur-3xl -z-10"></div>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Hero;
