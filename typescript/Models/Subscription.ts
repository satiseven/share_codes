import { DateTime } from 'luxon'
import { BaseModel, BelongsTo, belongsTo, column } from '@ioc:Adonis/Lucid/Orm'
import User from './User'
import Plan from './Plan'

export default class Subscription extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public planId: string

  @column()
  public userId: string

  @column()
  public stripeSubscriptionId: string

  @column()
  public stripeCustomerId: string

  @column()
  public paymentStatus: 'paid' | 'unpaid' | 'no_payment_required'

  @column()
  public billing: 'monthly' | 'yearly'

  @column()
  public isCanceled: boolean = false

  @column()
  public isExpired: boolean = false

  @column()
  public isDeleted: boolean = false

  @column.dateTime()
  public expiresAt: DateTime = DateTime.now() // TODO define auto expiry time maybe based on this.plan

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  @belongsTo(() => User)
  public user: BelongsTo<typeof User>

  @belongsTo(() => Plan)
  public plan: BelongsTo<typeof Plan>

  public get serializedSubscriptionInfo() {
    const {
      id,
      planId,
      userId,
      paymentStatus,
      billing,
      stripeSubscriptionId,
      stripeCustomerId,
      isCanceled,
      isExpired,
      isDeleted,
      expiresAt,
    } = this

    return {
      id,
      planId,
      userId,
      paymentStatus,
      billing,
      stripeSubscriptionId,
      stripeCustomerId,
      isCanceled,
      isExpired,
      isDeleted,
      expiresAt,
    }
  }
}
