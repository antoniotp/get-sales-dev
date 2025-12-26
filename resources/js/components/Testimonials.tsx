import { useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Star, ChevronLeft, ChevronRight } from "lucide-react";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import { Button } from "@/components/ui/button";

const Testimonials = () => {
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const testimonials = [
    {
      id: 1,
      name: "Diego Álvarez",
      role: "CEO",
      company: "Empresa Presente",
      quote: "Antes teníamos un caos en las comunicaciones: varias personas usando el mismo WhatsApp y muchos errores. Con GetSales creamos usuarios, asignamos cada conversación a su responsable y hoy tenemos todo centralizado y bajo control. Además, las automatizaciones nos permiten contactar a los huéspedes sin recordatorios manuales. Fue un antes y un después. Y el soporte técnico responde siempre rapidísimo.",
      rating: 5,
      industry: "#Hotelería"
    },
    {
      id: 2,
      name: "Patricio Gómez Pawelek",
      role: "CEO & Founder",
      company: "Jobomas",
      quote: "Gestionar cientos de conversaciones diarias con empresas y candidatos en distintos idiomas era insostenible. Requería mucho personal y aun así no dábamos abasto. Con GetSales hoy atendemos el 99 % de nuestras comunicaciones con IA, hacemos seguimiento automático de leads y potenciamos las ventas. Lo usamos tanto para soporte como para ventas. Es fantástico.",
      rating: 5,
      industry: "#Reclutamiento"
    },
    {
      id: 3,
      name: "Inés Huergo",
      role: "Directora",
      company: "Laboratorios Nobis",
      quote: "No teníamos una gestión real de clientes: nos contactaban, vendíamos y ahí terminaba todo. Con GetSales profesionalizamos toda la comunicación. La IA atiende la mayoría de los contactos y nosotros podemos enfocarnos en el producto, que es lo más importante. Funciona muy bien.",
      rating: 5,
      industry: "#Farmacéutica"
    },
    {
      id: 4,
      name: "Marina Snitcofsky",
      role: "Doctora en Veterinaria e Investigadora",
      company: "",
      quote: "Gestionar citas y enviar recordatorios uno por uno nos consumía muchísimo tiempo. Hoy GetSales se encarga automáticamente de los recordatorios y la organización de las citas. Nosotros podemos enfocarnos en atender mejor a los pacientes, con más tranquilidad y menos tareas administrativas.",
      rating: 5,
      industry: "#Veterinaria"
    }
  ];

  const toggleExpand = (id: number) => {
    setExpandedId(expandedId === id ? null : id);
  };

  const truncateText = (text: string, maxLength: number = 150) => {
    if (text.length <= maxLength) return text;
    return text.substring(0, maxLength) + "...";
  };

  return (
    <section id="testimonios" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">
            Testimonios de nuestros <span className="text-brandRed">clientes</span>
          </h2>
          <p className="text-lg text-gray-600">
            Empresas de distintos sectores han transformado su estrategia de ventas con GetSales.
          </p>
        </div>

        <Carousel
          opts={{
            align: "start",
            loop: true,
          }}
          className="w-full max-w-7xl mx-auto"
        >
          <CarouselContent className="-ml-4">
            {testimonials.map((testimonial) => (
              <CarouselItem key={testimonial.id} className="pl-4 md:basis-1/3 basis-full">
                <Card className="border-none shadow-lg h-full">
                  <CardContent className="p-6">
                    <div className="flex items-center gap-1 mb-4">
                      {[...Array(5)].map((_, i) => (
                        <Star 
                          key={i} 
                          size={16} 
                          className={i < testimonial.rating ? "fill-yellow-400 text-yellow-400" : "text-gray-300"} 
                        />
                      ))}
                    </div>
                    
                    <div className="mb-4">
                      <p className="text-gray-600 text-sm italic leading-relaxed">
                        "{expandedId === testimonial.id 
                          ? testimonial.quote 
                          : truncateText(testimonial.quote, 120)}"
                      </p>
                      {testimonial.quote.length > 120 && (
                        <Button 
                          variant="link" 
                          className="text-brandRed p-0 h-auto mt-2 text-sm"
                          onClick={() => toggleExpand(testimonial.id)}
                        >
                          {expandedId === testimonial.id ? "Ver menos" : "Ver más"}
                        </Button>
                      )}
                    </div>

                    <div className="flex flex-col gap-2">
                      <div>
                        <h4 className="font-bold text-base">{testimonial.name}</h4>
                        <p className="text-xs text-gray-500">
                          {testimonial.role}{testimonial.company && `, ${testimonial.company}`}
                        </p>
                      </div>
                      <span className="text-brandRed font-semibold text-xs bg-red-50 px-2 py-1 rounded-full w-fit">
                        {testimonial.industry}
                      </span>
                    </div>
                  </CardContent>
                </Card>
              </CarouselItem>
            ))}
          </CarouselContent>
          <CarouselPrevious className="hidden md:flex -left-12" />
          <CarouselNext className="hidden md:flex -right-12" />
        </Carousel>

        <div className="flex justify-center gap-2 mt-6 md:hidden">
          <p className="text-sm text-gray-500">Desliza para ver más testimonios</p>
        </div>
      </div>
    </section>
  );
};

export default Testimonials;
