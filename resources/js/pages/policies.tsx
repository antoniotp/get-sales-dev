import Navigation from '@/components/Navigation';
import Footer from '@/components/Footer';
import { Head } from '@inertiajs/react';

export default function Policies() {
    return (
        <>
            <Head title="Policies">
                <meta
                    name="description"
                    content="Política de privacidad de Get Sales. Información sobre cómo recopilamos, utilizamos y protegemos sus datos personales."
                />
                <meta name="robots" content="index, follow" />
                <link rel="canonical" href="https://getsales.aiscreener.io/policies" />
            </Head>
            <div className="min-h-screen flex flex-col">
                <Navigation />

                <main className="flex-grow pt-24 pb-16">
                    <div className="container-custom px-4">
                        <div className="max-w-4xl mx-auto">
                            <h1 className="text-3xl md:text-4xl font-bold mb-8">Política de Privacidad</h1>

                            <div className="prose prose-lg max-w-none">
                                <p className="text-gray-700 mb-6">
                                    Última actualización: 19 de Mayo de 2025
                                </p>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">1. Introducción</h2>
                                    <p>
                                        En Grupo Jobo S.A. (en adelante, "la Empresa"), nos comprometemos a proteger la privacidad y los datos personales de nuestros usuarios y clientes. Esta Política de Privacidad describe cómo recopilamos, utilizamos, compartimos y protegemos la información personal en el contexto de nuestros servicios que integran la plataforma WhatsApp Business proporcionada por Meta Platforms, Inc. (en adelante, "Meta").

                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">2. Información que Recopilamos</h2>
                                    <h3 className="text-2xl font-normal mb-4">2.1. Datos proporcionados por el usuario</h3>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li><strong>Información de contacto: </strong>nombre completo, número de teléfono, dirección de correo electrónico.</li>
                                        <li><strong>Datos de identificación: </strong>número de documento nacional de identidad o CUIT/CUIL.</li>
                                        <li><strong>Información comercial: </strong>nombre de la empresa, dirección fiscal, rubro de actividad.</li>
                                    </ul>

                                    <h3 className="text-2xl font-normal mb-4">2.2. Datos recopilados automáticamente</h3>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li><strong>Datos de uso: </strong>interacciones con nuestros servicios, incluyendo mensajes enviados y recibidos a través de WhatsApp Business.</li>
                                        <li><strong>Información técnica: </strong>dirección IP, tipo de dispositivo, sistema operativo, navegador, identificadores únicos de dispositivos.</li>
                                    </ul>

                                    <h3 className="text-2xl font-normal mb-4">2.3. Datos obtenidos a través de WhatsApp Business</h3>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li><strong>Información de la cuenta de WhatsApp: </strong>número de teléfono asociado, nombre de perfil, estado.</li>
                                        <li><strong>Mensajes: </strong>contenido de los mensajes enviados y recibidos, incluyendo plantillas de mensajes (templates) utilizadas.</li>
                                        <li><strong>Metadatos: </strong>fecha y hora de los mensajes, estado de entrega y lectura.</li>
                                    </ul>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">3. Finalidades del Tratamiento de Datos
                                    </h2>
                                    <p>
                                        Utilizamos la información recopilada para las siguientes finalidades:
                                    </p>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li>Proporcionar y gestionar nuestros servicios de mensajería a través de WhatsApp Business</li>
                                        <li>Personalizar y mejorar la experiencia del usuario.</li>
                                        <li>Enviar comunicaciones comerciales y promocionales, previa obtención del consentimiento correspondiente.</li>
                                        <li>Cumplir con obligaciones legales y regulatorias.</li>
                                        <li>Prevenir fraudes y garantizar la seguridad de nuestros sistemas.</li>
                                    </ul>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">4. Base Legal para el Tratamiento de Datos
                                    </h2>
                                    <p>
                                        Tratamos los datos personales sobre la base de:
                                    </p>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li><strong>Consentimiento: </strong>cuando el usuario ha otorgado su consentimiento explícito para el tratamiento de sus datos con fines específicos.
                                        </li>
                                        <li><strong>Relación contractual: </strong>cuando el tratamiento es necesario para la ejecución de un contrato en el que el usuario es parte.
                                        </li>
                                        <li><strong>Obligaciones legales: </strong>para cumplir con obligaciones legales a las que estamos sujetos.
                                        </li>
                                        <li><strong>Intereses legítimos: </strong>para fines comerciales legítimos, siempre que no prevalezcan los derechos y libertades fundamentales del usuario.
                                        </li>
                                    </ul>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">5. Obtención del Consentimiento (Opt-In)
                                    </h2>
                                    <p>
                                        Antes de enviar mensajes a través de WhatsApp Business, obtenemos el consentimiento explícito del usuario, conforme a las políticas de Meta y la legislación aplicable. Los métodos de obtención de consentimiento incluyen:
                                    </p>
                                    <ul className="list-disc pl-6 mt-3 space-y-2">
                                        <li>Formularios en nuestro sitio web.</li>
                                        <li>Mensajes SMS con enlaces de confirmación.</li>
                                        <li>Interacciones telefónicas grabadas (IVR).</li>
                                        <li>Formularios físicos firmados por el usuario.</li>
                                        <li>El consentimiento incluye información clara sobre:</li>
                                        <li>La identidad de la Empresa.</li>
                                        <li>La finalidad de las comunicaciones.</li>
                                        <li>La posibilidad de revocar el consentimiento en cualquier momento.</li>
                                    </ul>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">6. Derechos del Usuario</h2>
                                    <p>
                                        El usuario tiene los siguientes derechos respecto a sus datos personales:
                                    </p>
                                    <p className="mt-3">
                                        <li><strong>Acceso: </strong>conocer qué datos personales tratamos.</li>
                                        <li><strong>Rectificación: </strong>solicitar la corrección de datos inexactos o incompletos.
                                        </li>
                                        <li><strong>Cancelación: </strong>solicitar la eliminación de sus datos cuando ya no sean necesarios.
                                        </li>
                                        <li><strong>Oposición: </strong>oponerse al tratamiento de sus datos por motivos legítimos.</li>
                                        <li><strong>Portabilidad: </strong>recibir sus datos en un formato estructurado y transferirlos a otro responsable.
                                        </li>
                                        <li><strong>Revocación del consentimiento: </strong>retirar su consentimiento en cualquier momento.</li>
                                    </p>
                                    <p>Para ejercer estos derechos, el usuario puede contactarnos a través de <a className="text-blue-800" href={'mailto:pgomez@jobomas.com'}>pgomez@jobomas.com</a>.
                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">7. Compartición de Datos</h2>
                                    <p>
                                        No compartimos datos personales con terceros, excepto en los siguientes casos:
                                    </p>
                                    <p className="mt-3">
                                        <li><strong>Proveedores de servicios: </strong>empresas que prestan servicios en nuestro nombre, bajo acuerdos de confidencialidad y conformidad con esta política.
                                        </li>
                                        <li><strong>Cumplimiento legal: </strong>cuando sea requerido por ley o en respuesta a procesos legales.
                                        </li>
                                        <li><strong>Transferencias internacionales: </strong>en caso de transferencias fuera del país, garantizamos un nivel adecuado de protección de datos conforme a la legislación aplicable.
                                        </li>
                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">8. Seguridad de los Datos</h2>
                                    <p>
                                        Implementamos medidas técnicas y organizativas apropiadas para proteger los datos personales contra pérdida, uso indebido, acceso no autorizado, divulgación, alteración y destrucción. Estas medidas incluyen:
                                    </p>
                                    <p className="mt-3">
                                        <li>Cifrado de datos en tránsito y en reposo.</li>
                                        <li>Control de acceso basado en roles.</li>
                                        <li>Auditorías y monitoreo de seguridad periódicos.</li>
                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">9. Retención de Datos</h2>
                                    <p>
                                        Conservamos los datos personales durante el tiempo necesario para cumplir con las finalidades descritas en esta política, salvo que la ley requiera o permita un período de retención más largo. Los criterios para determinar los períodos de retención incluyen:
                                    </p>
                                    <p className="mt-3">
                                        <li>Duración de la relación contractual.</li>
                                        <li>Requisitos legales y regulatorios.</li>
                                        <li>Necesidades operativas y comerciales.</li>
                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">10. Uso de WhatsApp Business y Meta</h2>
                                    <p>
                                        Al utilizar nuestros servicios que integran WhatsApp Business, el usuario reconoce que sus datos pueden ser tratados por Meta conforme a sus propias políticas de privacidad. Para más información, consulte:
                                    </p>
                                    <p className="mt-3">
                                        <li>Política de privacidad de WhatsApp: <a href={'https://www.whatsapp.com/legal/privacy-policy'} className="text-blue-800" target="_blank" rel="noopener">https://www.whatsapp.com/legal/privacy-policy</a></li>
                                        <li>Política de privacidad de Meta: <a href={"https://www.facebook.com/privacy/policy"} className="text-blue-800" target="_blank" rel="noopener">https://www.facebook.com/privacy/policy</a></li>
                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">11. Cambios en la Política de Privacidad</h2>
                                    <p>
                                        Nos reservamos el derecho de modificar esta Política de Privacidad en cualquier momento. Notificaremos cualquier cambio mediante la publicación de la nueva política en nuestro sitio web y, cuando corresponda, a través de otros medios.

                                    </p>
                                </section>

                                <section className="mb-10">
                                    <h2 className="text-2xl font-semibold mb-4">12. Contacto</h2>
                                    <p>
                                        Si tiene preguntas o inquietudes sobre esta Política de Privacidad o sobre nuestras prácticas de tratamiento de datos, puede contactarnos en:
                                    </p>
                                    <p className="mt-3">
                                        <li><strong>Correo electrónico: </strong>pgomez@jobomas.com</li>
                                        <li><strong>Dirección postal: </strong>Roseti 2052, (1425) Ciudad de Buenos Aires, Argentina.</li>
                                        <li><strong>Fecha de entrada en vigor: </strong>01 de Mayo de 2025</li>
                                    </p>
                                </section>
                            </div>
                        </div>
                    </div>
                </main>
                <Footer />
            </div>
        </>
    );
}
