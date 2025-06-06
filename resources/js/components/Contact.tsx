import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { useToast } from '@/components/ui/use-toast';
import { Mail, MapPin, MessageSquare, Phone } from 'lucide-react';
import { useState } from 'react';

const Contact = () => {
    const { toast } = useToast();
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        message: '',
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        // Aquí iría la lógica de envío del formulario

        toast({
            title: 'Mensaje enviado',
            description: 'Nos pondremos en contacto contigo pronto.',
        });

        setFormData({
            name: '',
            email: '',
            phone: '',
            message: '',
        });
    };

    return (
        <section id="contacto" className="section-padding bg-white">
            <div className="container-custom">
                <div className="grid grid-cols-1 gap-12 lg:grid-cols-2">
                    <div>
                        <h2 className="mb-6 text-3xl font-bold md:text-4xl dark:text-gray-950">
                            ¿Listo para <span className="text-brandBlue">potenciar tus ventas</span>?
                        </h2>
                        <p className="mb-8 text-lg text-gray-600">
                            Contáctanos hoy mismo para una demostración gratuita de cómo WhatsAgents puede transformar tu negocio.
                        </p>

                        <div className="mb-8 space-y-6 dark:text-gray-950">
                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <Phone className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Llámanos</h3>
                                    <p className="text-gray-600">+34 900 123 456</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <Mail className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Email</h3>
                                    <p className="text-gray-600">info@whatsagents.com</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-brandBlue/10 p-3">
                                    <MapPin className="h-6 w-6 text-brandBlue" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">Oficina</h3>
                                    <p className="text-gray-600">Calle Gran Vía 50, Madrid, España</p>
                                </div>
                            </div>

                            <div className="flex items-start gap-4">
                                <div className="rounded-full bg-whatsapp/10 p-3">
                                    <MessageSquare className="h-6 w-6 text-whatsapp" />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">WhatsApp</h3>
                                    <p className="text-gray-600">+34 600 123 456</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-8 shadow-xl dark:text-gray-950">
                        <h3 className="mb-6 text-2xl font-bold">Envíanos un mensaje</h3>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div>
                                <label htmlFor="name" className="mb-2 block text-sm font-medium">
                                    Nombre completo
                                </label>
                                <Input id="name" name="name" value={formData.name} onChange={handleChange} placeholder="Escribe tu nombre" required />
                            </div>

                            <div>
                                <label htmlFor="email" className="mb-2 block text-sm font-medium">
                                    Email
                                </label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={formData.email}
                                    onChange={handleChange}
                                    placeholder="ejemplo@correo.com"
                                    required
                                />
                            </div>

                            <div>
                                <label htmlFor="phone" className="mb-2 block text-sm font-medium">
                                    Teléfono
                                </label>
                                <Input id="phone" name="phone" value={formData.phone} onChange={handleChange} placeholder="+34 600 000 000" />
                            </div>

                            <div>
                                <label htmlFor="message" className="mb-2 block text-sm font-medium">
                                    Mensaje
                                </label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    value={formData.message}
                                    onChange={handleChange}
                                    placeholder="¿Cómo podemos ayudarte?"
                                    rows={4}
                                    required
                                    className="dark:bg-white dark:text-gray-950"
                                />
                            </div>

                            <Button type="submit" className="w-full bg-brandBlue py-6 hover:bg-brandBlue/90">
                                Enviar mensaje
                            </Button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    );
};

export default Contact;
