import type { HttpContextContract } from '@ioc:Adonis/Core/HttpContext'
import Subscription from 'App/Models/Subscription'
import User from 'App/Models/User'
import CreateSubscriptionValidator from 'App/Validators/Subscription/CreateSubscriptionValidator'
import UpdateSubscriptionValidator from 'App/Validators/Subscription/UpdateSubscriptionValidator'
import Stripe from '@ioc:Adonis/Addons/Stripe'
import CreatePaymentValidator from 'App/Validators/Subscription/CreatePaymentValidator'
import Payment from 'App/Services/Subscription/Payment'
import PaymentException from 'App/Exceptions/PaymentException'
import Plan from 'App/Models/Plan'

export default class SubscriptionController {
  public async show({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const subscription = await Subscription.query().where({ userId: user.id }).firstOrFail()

    return {
      data: {
        subscription: subscription.serializedSubscriptionInfo,
      },
    }
  }

  public async create({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const { planId, paymentRequestId } = await request.validate(CreateSubscriptionValidator)

    const service = new Payment(planId)
    const {
      payment_status: paymentStatus,
      status,
      subscription: stripeSubscriptionId,
      customer: stripeCustomerId,
    } = await service.getPaymentRequest(paymentRequestId)

    if (paymentStatus !== 'paid' && status !== 'complete') {
      throw new PaymentException('Payment is not done, please pay then try again!', 403)
    }

    const subscription = await Subscription.firstOrCreate(
      { userId: user.id },
      {
        userId: user.id,
        planId,
        paymentStatus,
        stripeSubscriptionId: stripeSubscriptionId as string,
        stripeCustomerId: stripeCustomerId as string,
      }
    )

    return {
      data: {
        message: 'Subscription created successfully',
        subscription: subscription.serializedSubscriptionInfo,
      },
    }
  }

  public async update({ auth, request }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const subscription = await Subscription.query().where({ userId: user.id }).firstOrFail()
    const { isCanceled, planId } = await request.validate(UpdateSubscriptionValidator)
    const plan = await Plan.query().where({ id: planId }).firstOrFail()
    const stripeSubscription = await Stripe.subscriptions.retrieve(
      subscription.stripeSubscriptionId
    )
    const updatedStripeSubscription = await Stripe.subscriptions.update(stripeSubscription.id, {
      items: [
        {
          id: stripeSubscription.items.data[0].id,
          price: plan.stripePlanId,
        },
      ],
    })

    const updateAttrs = { stripeSubscriptionId: updatedStripeSubscription.id, isCanceled, planId }
    const updateSubscription = await subscription.merge(updateAttrs).save()

    return {
      data: {
        message: 'Subscription updated successfully',
        subscription: updateSubscription.serializedSubscriptionInfo,
      },
    }
  }

  public async destroy({ auth }: HttpContextContract) {
    const user: User = auth.use('api').user!
    const subscription = await Subscription.query().where({ userId: user.id }).firstOrFail()

    await Stripe.subscriptions.update(subscription.stripeSubscriptionId, {
      cancel_at_period_end: true,
    })
    await subscription.merge({ isCanceled: true }).save()

    return {
      data: {
        message: 'Subscription deleted successfully',
      },
    }
  }

  public async payment({ request, auth }: HttpContextContract) {
    const { planId } = await request.validate(CreatePaymentValidator)
    const user: User = auth.use('api').user!

    await user.load('subscription')

    const service = new Payment(planId)
    const paymentRequest = await service.createPaymentRequest(user)

    return {
      data: {
        id: paymentRequest.id,
        url: paymentRequest.url,
      },
    }
  }

  public async showInvoices({}: HttpContextContract) {}
}
