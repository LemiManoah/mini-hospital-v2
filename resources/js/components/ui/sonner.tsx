import {
  CircleCheckIcon,
  InfoIcon,
  Loader2Icon,
  OctagonXIcon,
  TriangleAlertIcon,
} from "lucide-react"
import { useTheme } from "next-themes"
import type { CSSProperties } from "react"
import { Toaster as Sonner, type ToasterProps } from "sonner"

const Toaster = ({ ...props }: ToasterProps) => {
  const { theme = "system" } = useTheme()

  return (
    <Sonner
      position="top-right"
      theme={theme as ToasterProps["theme"]}
      className="toaster group"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      toastOptions={{
        classNames: {
          toast:
            "w-[min(30rem,calc(100vw-2rem))] rounded-xl border-0 px-4 py-4 shadow-xl",
          title: "text-sm font-semibold",
          description: "text-sm opacity-95",
          success:
            "!bg-emerald-600 !text-white [&_[data-icon]]:!text-white",
          error:
            "!bg-rose-600 !text-white [&_[data-icon]]:!text-white",
          info:
            "!bg-sky-600 !text-white [&_[data-icon]]:!text-white",
          warning:
            "!bg-amber-300 !text-amber-950 [&_[data-icon]]:!text-amber-950",
          loading:
            "!bg-slate-900 !text-white dark:!bg-slate-100 dark:!text-slate-950",
          closeButton:
            "!border-white/20 !bg-white/10 !text-current hover:!bg-white/20",
        },
      }}
      style={
        {
          "--normal-bg": "var(--popover)",
          "--normal-text": "var(--popover-foreground)",
          "--normal-border": "var(--border)",
          "--border-radius": "var(--radius)",
        } as CSSProperties
      }
      {...props}
    />
  )
}

export { Toaster }
