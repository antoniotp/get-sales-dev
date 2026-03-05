import {
    Pagination as PaginationContainer,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import { router } from '@inertiajs/react';
import React from 'react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useTranslation } from 'react-i18next';

interface PaginationProps {
    links: { url: string | null; label: string; active: boolean }[];
    windowSize?: number;
    previousLabel?: string;
    nextLabel?: string;
}

export function CustomPagination({
    links,
    windowSize = 5,
    previousLabel,
    nextLabel,
}: PaginationProps) {

    const { t } = useTranslation('general');

    const prevText = previousLabel ?? t('pagination.previous');
    const nextText = nextLabel ?? t('pagination.next');

    function handlePaginationClick(url: string | null) {
        if (!url) return;
        router.get(url, {},
            {
                preserveState: true,
                preserveScroll: true,
            });
    }

    const allPageLinks = links.slice(1, -1);
    let windowedPageLinks = allPageLinks;

    if (allPageLinks.length > windowSize) {
        const currentPageIndex = allPageLinks.findIndex(link => link.active);
        if (currentPageIndex !== -1) {
            const half = Math.floor(windowSize / 2);
            let start = currentPageIndex - half;
            let end = currentPageIndex + half;
            if (start < 0) {
                start = 0;
                end = windowSize - 1;
            }
            if (end >= allPageLinks.length) {
                end = allPageLinks.length - 1;
                start = end - (windowSize - 1);
            }
            const window = allPageLinks.slice(start, end + 1);
            const result = [];
            if (start > 0) {
                result.push({ url: null, label: '...', active: false });
            }
            result.push(...window);
            if (end < allPageLinks.length - 1) {
                result.push({ url: null, label: '...', active: false });
            }
            windowedPageLinks = result;
        } else {
            windowedPageLinks = allPageLinks.slice(0, windowSize);
        }
    }

    return (
        <PaginationContainer>
            <PaginationContent>
                {links.length > 3 && (
                    <>
                        <PaginationItem>
                            <PaginationLink
                                aria-label={t('pagination.go_previous')}
                                size="default"
                                href={links[0].url || ''}
                                className={cn("gap-1 px-2.5 sm:pl-2.5", !links[0].url ? 'opacity-50 cursor-not-allowed' : '')}
                                onClick={(e: React.MouseEvent) => {
                                    e.preventDefault();
                                    if (links[0].url) {
                                        handlePaginationClick(links[0].url);
                                    }
                                }}
                            >
                                <ChevronLeftIcon className="h-4 w-4" />
                                <span className="hidden sm:block">{prevText}</span>
                            </PaginationLink>
                        </PaginationItem>

                        {windowedPageLinks.map((link, index) => {
                            if (!link.url) {
                                return (
                                    <PaginationItem key={index}>
                                        <PaginationEllipsis />
                                    </PaginationItem>
                                )
                            }
                            return (
                                <PaginationItem key={index}>
                                    <PaginationLink
                                        href={link.url}
                                        isActive={link.active}
                                        onClick={(e: React.MouseEvent) => {
                                            e.preventDefault();
                                            handlePaginationClick(link.url);
                                        }}
                                    >
                                        {link.label}
                                    </PaginationLink>
                                </PaginationItem>
                            )
                        })}

                        <PaginationItem>
                            <PaginationLink
                                aria-label={t('pagination.go_next')}
                                size="default"
                                href={links[links.length - 1].url || ''}
                                className={cn("gap-1 px-2.5 sm:pr-2.5", !links[links.length - 1].url ? 'opacity-50 cursor-not-allowed' : '')}
                                onClick={(e: React.MouseEvent) => {
                                    e.preventDefault();
                                    if (links[links.length - 1].url) {
                                        handlePaginationClick(links[links.length - 1].url);
                                    }
                                }}
                            >
                                <span className="hidden sm:block">{nextText}</span>
                                <ChevronRightIcon className="h-4 w-4" />
                            </PaginationLink>
                        </PaginationItem>
                    </>
                )}
            </PaginationContent>
        </PaginationContainer>
    );
}
