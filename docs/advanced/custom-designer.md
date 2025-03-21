---
title: Custom Designer
weight: 7
---

## Use Custom Designer

The trait `Designer` is the one responsible for presenting the form in the frontend, and now you can customize it to your liking.

> **Note**\
> This is an advanced feature; please use it only when necessary since you have to mainline it manually with every update for Bolt.

### First, copy the trait to your app

Copy the trait from `\LaraZeus\Bolt\Concerns` to your app, lets say: `\App\Zeus\Bolt\Concerns`

### Call the trait in a service provider

In your register method of your `AppServiceProvider` add the following:

```php
\LaraZeus\Bolt\Filament\Resources\FormResource::getBoltFormSchemaUsing(fn(): array => \App\Zeus\Bolt\Concerns\Designer::getMainFormSchema());
```

You're done. Customize the form builder to fit your needs. Remember to keep an eye on any changes in future updates so that you avoid breaking changes.
