import { useState } from "react";
import { Card, CardContent } from "@/components/ui/card";
import { Star } from "lucide-react";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  CarouselNext,
  CarouselPrevious,
} from "@/components/ui/carousel";
import { Button } from "@/components/ui/button";
import { useTranslation } from "react-i18next";

type Testimonial = {
  id: number;
  name: string;
  role: string;
  company: string;
  quote: string;
  rating: number;
  industry: string;
};

const Testimonials = () => {
  const { t } = useTranslation("home");
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const testimonials = t("testimonials.items", {
    returnObjects: true
  }) as Testimonial[];

  const toggleExpand = (id: number) => {
    setExpandedId(expandedId === id ? null : id);
  };

  const truncateText = (text: string, maxLength = 120) =>
    text.length <= maxLength ? text : text.substring(0, maxLength) + "...";

  return (
    <section id="testimonios" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-2xl mx-auto mb-16">
          <h2 className="text-3xl md:text-4xl font-bold mb-4">
            {t("testimonials.title.prefix")} <span className="text-brandRed">{t("testimonials.title.highlight")}</span>
          </h2>
          <p className="text-lg text-gray-900">
            {t("testimonials.description")}
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
                  <CardContent>
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
                      <p className="text-gray-900 text-sm italic leading-relaxed">
                        "{expandedId === testimonial.id 
                          ? testimonial.quote 
                          : truncateText(testimonial.quote)}"
                      </p>
                      {testimonial.quote.length > 120 && (
                        <Button 
                          variant="link" 
                          className="text-brandRed p-0 h-auto mt-2 text-sm"
                          onClick={() => toggleExpand(testimonial.id)}
                        >
                          {expandedId === testimonial.id
                            ? t("testimonials.cta.showLess")
                            : t("testimonials.cta.showMore")}
                        </Button>
                      )}
                    </div>

                    <div className="flex flex-col gap-2">
                      <div>
                        <h4 className="font-bold text-base">{testimonial.name}</h4>
                        <p className="text-xs text-gray-900">
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
          <p className="text-sm text-gray-500">{t("testimonials.cta.mobileHint")}</p>
        </div>
      </div>
    </section>
  );
};

export default Testimonials;
