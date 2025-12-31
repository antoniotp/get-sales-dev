import { Users, MessageSquare, Bot, TrendingUp, Zap, Clock, Rocket } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";
import { useTranslation } from "react-i18next";

const Features = () => {
  const { t } = useTranslation("home");

  const features = t("features.list", {
    returnObjects: true,
  }) as { title: string; description: string }[];

  const featuresIcons = [
    <Bot className="h-10 w-10 text-brandRed" />,
    <MessageSquare className="h-10 w-10 text-whatsapp" />,
    <Clock className="h-10 w-10 text-brandRed" />,
    <Users className="h-10 w-10 text-whatsapp" />,
    <TrendingUp className="h-10 w-10 text-brandRed" />,
    <Zap className="h-10 w-10 text-whatsapp" />
  ];

  return (
    <section id="features" className="section-padding bg-gray-50">
      <div className="container-custom">
        <div className="text-center max-w-3xl mx-auto mb-16">
          <div className="flex items-center justify-center gap-2 mb-4">
            <h2 className="text-3xl md:text-4xl font-bold">
              {t("features.title.prefix")} <span className="text-brandRed">{t("features.title.highlight")}</span>
            </h2>
            <Rocket className="h-8 w-8 text-brandRed" />
          </div>
          <p className="text-lg text-gray-600">
            {t("features.description")}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          {features.map((feature, index) => (
            <Card
              key={index}
              className="border-none shadow-lg hover:shadow-xl transition-shadow overflow-hidden bg-white"
            >
              <CardContent>
                <div className="mb-6">
                  {featuresIcons[index]}
                </div>

                <h3 className="text-xl font-bold mb-2 text-gray-950">
                  {feature.title}
                </h3>

                <p className="text-gray-600">
                  {feature.description}
                </p>
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    </section>
  );
};

export default Features;
