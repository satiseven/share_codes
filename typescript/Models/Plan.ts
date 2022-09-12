import { DateTime } from 'luxon'
import { BaseModel, column } from '@ioc:Adonis/Lucid/Orm'

export default class Plan extends BaseModel {
  @column({ isPrimary: true })
  public id: string

  @column()
  public name: string

  // TODO maybe compute/update before fetch from Stripe
  @column()
  public price: number

  @column()
  public billing: 'monthly' | 'yearly'

  @column()
  public isActive: boolean = true

  @column()
  public isDeleted: boolean = false

  @column()
  public stripePlanId: string

  @column.dateTime({ autoCreate: true })
  public createdAt: DateTime

  @column.dateTime({ autoCreate: true, autoUpdate: true })
  public updatedAt: DateTime

  public get serializedPlanInfo() {
    const { id, name, price, billing, stripePlanId, isActive, isDeleted } = this
    return { id, name, price, billing, stripePlanId, isActive, isDeleted }
  }
}
